<?php
namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use LuminateOne\RevisionTracking\Models\Revision;
use LuminateOne\RevisionTracking\RevisionTracking;

trait Revisionable
{
    /**
     * Holds the parent revision information
     * @var array
     */
    public $parentRevision = null;

    /**
     * Holds the parent model name
     * @var string
     */
    public $parentModelName = null;

    /**
     * Holds the created revision
     * @var Revision
     */
    public $createdRevision = null;

    /**
     * Indicates that this model is using the Revisionable Trait
     * @var bool
     */
    public $usingRevisionableTrait = true;

    /**
     *  Catch the updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::created(function ($model) {
            // $model->trackChanges();
        });

        static::updated(function ($model) {
            // \Log::info(print_r($model, true));
            $model->trackChanges();
        });

        static::deleted(function ($model) {
            // $model->trackChanges();
            RevisionTracking::eloquentDelete($model);
        });
    }

    /**
     * Find and store the changes as a revision for the current model.
     *
     * @throws ErrorException If the current model does not have a primary.
     */
    public function trackChanges()
    {
        if (!$this->getKeyName()) {
            throw new ErrorException("The Revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        $originalFields = RevisionTracking::eloquentDiff($this);

        RevisionTracking::eloquentStoreDiff($this, $originalFields);

        $this->addThisRevisionToChildRelation($this->createdRevision, $this, $this);
        $this->addThisRevisionToParentRelation();
    }

    /**
     * Add this revision to each of its child models as parentRevision,
     *
     * @param Revision $revision The newly created revision of the model
     * @param Model $currentModel The model`s relations that the revision will be assigned to
     * @param Model $parentModel parentModel
     */
    public function addThisRevisionToChildRelation($revision, $currentModel, $parentModel)
    {
        foreach ($currentModel->relations as $relations) {
            $relations = $relations instanceof Collection ? $relations->all() : [$relations];

            foreach (array_filter($relations) as $aRelation) {
                if ($aRelation->usingRevisionableTrait) {
                    $aRelation->parentRevision = $revision;
                    $aRelation->parentModelName = get_class($parentModel);
                } else {
                    // If the current relation is not using the Revisionable Trait, then we need to go deeper to find its child relations,
                    // We need to always use `$this` to call the addThisRevisionToChildRelation recursively,
                    // because when a model without the revision control turned on involved,
                    // it will break the recursive loop
                    $this->addThisRevisionToChildRelation($revision, $aRelation, $parentModel);
                }
            }
        }
    }

    /**
     * Add the this revision to its parent revision as child revision
     */
    public function addThisRevisionToParentRelation()
    {
        if (!$this->parentRevision) {
            return;
        }

        $childRevision = $this->parentRevision->original_values['child_revisions'];
        if (!$childRevision) {
            $childRevision = [];
        }
        array_push($childRevision, $this->relationalRevisionIdentifier('self'));
        $this->parentRevision->original_values['child_revisions'] = $childRevision;

        $this->parentRevision->save();
    }

    /**
     * Restoring the revision.
     * Using the model name and the revision ID provided to retrieve the revision for the model
     *
     * @param integer $revisionId Revision ID for the model
     * @param boolean $saveAsRevision true =>  save the “rollback” as a new revision of the model
     *                                false => rollback to a specific revision and delete all the revisions that came
     *                                         after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollback($revisionId, $saveAsRevision = true)
    {
        $targetRevision = $this->getRevision($revisionId);

        if (!$targetRevision) {
            throw new ErrorException("Revision " . $revisionId . " was not found for model " . get_class($this));
        }

        if (empty($targetRevision->original_values)) {
            $this->delete();
        } else {
            foreach ($targetRevision->original_values as $key => $value) {
                $this[$key] = $value;
            }
            $this->save();
        }

        if ($targetRevision->child_revisions) {
            foreach ($targetRevision->child_revisions as $aChildRevision) {
                $childModel = $this->getModelFromRelationalRevision($aChildRevision);
                if ($childModel->usingRevisionableTrait) {
                    $childModel->parentRevision = $this->allRevisions()->latest('id')->first();
                    $childModel->parentModelName = get_class($this);
                    $childModel->rollback($aChildRevision['revision_id'], $saveAsRevision);
                }
            }
        }

        if (!$saveAsRevision) {
            $this->allRevisions()->where('id', '>=', $revisionId)->delete();
        }
    }

    /**
     * Restoring the revision.
     * Using the model name and the revision ID provided to retrieve the revision for the model
     *
     * @param integer $revisionId Revision ID for the model
     * @param boolean $saveAsRevision true =>  save the “rollback” as a new revision of the model
     *                                false => rollback to a specific revision and delete all the revisions that came
     *                                           after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollbackWithRelation($revisionId, $saveAsRevision = true)
    {
        $relationalRevision = $this->getRelationalRevision($revisionId);
        $relationalModel = $this;

        if (!$relationalRevision) {
            throw new ErrorException("No relational revisions found for " . get_class($this) . " model");
        }

        if (!$relationalRevision->parent_revision && !$relationalRevision->child_revision) {
            throw new ErrorException("No relational revisions found for " . get_class($this) . " model");
        }

        while ($relationalRevision->original_values['parent_revision']) {
            $parentRevision = $relationalRevision->original_values['parent_revision'];
            $relationalModel = $this->getModelFromRelationalRevision($parentRevision);
            $revisionId = $this->revisionMode() === 'all' ? $parentRevision : $parentRevision['revision_id']
            $relationalRevision = $relationalModel->getRevision($revisionId);
        }

        if (!$relationalRevision->child_revisions) {
            throw new ErrorException("Child revisions not found for " . get_class($relationalModel) . " model");
        }

        $relationalModel->rollback($relationalRevision->id, $saveAsRevision);
    }

    /**
     * Get the target model from the revision
     *
     * @param $revisionInfo
     * @return mixed
     */
    public function getModelFromRelationalRevision($parentRevision)
    {
        $revision = null;
        $modelName = null;

        if($this->revisionMode() === 'all'){
            $revision = Revision::find($parentRevision);
            $modelName = $revision->model_name;
        } else {
            $modelName = $parentRevision['model_name'];
            $revision = (new $modelName())->getRevisionModel()->find($parentRevision['revision_id']);
        }

        return (new $modelName())->where($revision->model_identifier)->first();
    }

    /**
     * Get a specific revision by the revision ID.
     *
     * @param $revisionId       Revision ID for the model
     *
     * @return Revision         A single revision model
     * @throws ErrorException   If the revision table cannot be found
     */
    public function getRevision($revisionId)
    {
        return $this->allRevisions()->where('id', $revisionId)->first();
    }

    /**
     * Get all revisions for this model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws ErrorException   If the revision table cannot be found
     */
    public function allRevisions()
    {
        $targetRevision = $this->getRevisionModel()->where('model_identifier', $this->modelIdentifier(true));
        if ($this->revisionMode() === 'all') {
            $targetRevision = $targetRevision->where('model_name', get_class($this));
        }

        return $targetRevision;
    }

    /**
     * Get a specific relational revision by the revision ID.
     *
     * @param $revisionId       Revision ID for the model
     *
     * @return Revision         A single revision model
     * @throws ErrorException   If the revision table cannot be found
     */
    public function getRelationalRevision($revisionId)
    {
        return $this->allRelationalRevisions()->where('id', $revisionId)->first();
    }

    /**
     * Get all relational revisions for this model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws ErrorException   If the revision table cannot be found
     */
    public function allRelationalRevisions()
    {
        return $this->allRevisions()->where(function ($query) {
            $query->where('parent_revision', '!=', '')
                ->orWhere('child_revisions', '!=', '');
        });
    }

    /**
     * Get the Model for the revision with the correct table by checking the revision mode
     *
     * @throws ErrorException   If the revision table cannot be found
     *
     * @return Revision         An Eloquent model for the revision
     */
    public function getRevisionModel()
    {
        $revisionTableName = 'revisions';

        if ($this->revisionMode() === 'single') {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
        }

        if (!Schema::hasTable($revisionTableName)) {
            if ($this->revisionMode() === 'all') {
                throw new ErrorException("Revision table " . $revisionTableName . " not found. Please run `php artisan migrate to create the revision table");
            }
            throw new ErrorException("Revision table " . $revisionTableName . " not found. Please run php artisan table:revision to create revision tables");
        }

        $revisionModel = new Revision();
        $revisionModel->setTable($revisionTableName);

        return $revisionModel;
    }

    /**
     * A function to create the model identifier
     *
     * @param boolean $serialize Serialize the model identifier or not
     * @return mixed
     */
    public function modelIdentifier($serialize = false)
    {
        $modelIdentifier = [$this->getKeyName() => $this->getKey()];

        if ($serialize) {
            return serialize($modelIdentifier);
        }

        return $modelIdentifier;
    }

    /**
     * A function to create the relational identifier as parent or child
     * It indicates that which model and revision to use to create the
     * relational revision identifier
     *
     * @param string $as
     * @param boolean $serialize Serialize the model identifier or not
     *
     * @return mixed
     */
    public function relationalRevisionIdentifier($as = 'parent')
    {
        $revision = $this->createdRevision;
        $modelName = get_class($this);

        if ($as === 'parent') {
            $revision = $this->parentRevision;
            $modelName = $this->parentModelName;
        }

        if($this->revisionMode() === 'all'){
            return $revision->id;
        }

        return [
            'revision_id' => $revision->id,
            'model_name' => $modelName
        ];
    }

    /**
     * Read the config to get the current revision mode
     *
     * @return string Represents the revision mode
     */
    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }
}