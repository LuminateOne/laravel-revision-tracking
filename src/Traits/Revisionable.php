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
     * Holds the parent model
     * @var string
     */
    public $parentModel = null;

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

    public function setAsRelationalRevision(){
        $this->addThisModelToItsChildModel($this, $this);
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

        $this->addThisModelToItsChildModel($this, $this);
        $this->updateRelatedRevisions();
    }

    /**
     * Add this model to each of its child models as parentModel,
     *
     * @param Model $currentModel The model`s relations that the revision will be assigned to
     * @param Model $parentModel parentModel
     */
    public function addThisModelToItsChildModel($currentModel, $parentModel)
    {
        foreach ($currentModel->relations as $relations) {
            $relations = $relations instanceof Collection ? $relations->all() : [$relations];

            foreach (array_filter($relations) as $aRelation) {
                if ($aRelation->usingRevisionableTrait) {
                    $aRelation->parentModel = $parentModel;
                } else {
                    // If the current relation is not using the Revisionable Trait, then we need to go deeper to find its child relations,
                    // We need to always use `$this` to call the addThisModelToItsChildModel recursively,
                    // because when a model without the revision control turned on involved,
                    // it will break the recursive loop
                    $this->addThisModelToItsChildModel($aRelation, $parentModel);
                }
            }
        }
    }

    /**
     * Update parent revision and self revision
     */
    public function updateRelatedRevisions()
    {
        if (!$this->parentModel) {
            return;
        }

        if(!$this->parentModel->createdRevision){
            RevisionTracking::eloquentStoreDiff($this->parentModel, null);
        }

        $this->parentModel->createdRevision->addChildRevision($this->self_revision_identifier);

        $this->createdRevision->addParentRevision($this->parent_revision_identifier);
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

        if (array_key_exists('original_values', $targetRevision->revisions)) {
            if (empty($targetRevision->original_values)) {
                $this->delete();
            } else {
                foreach ($targetRevision->original_values as $key => $value) {
                    $this[$key] = $value;
                }
                $this->save();
            }
        }

        if (array_key_exists('child', $targetRevision->revisions)) {
            foreach ($targetRevision->child_revisions as $aChildRevision) {
                $modelName = $aChildRevision['model_name'];
                $revision = $this->getTargetRevision($aChildRevision);
                $childModel = (new $modelName())->where($revision->model_identifier)->first();

                if ($childModel->usingRevisionableTrait) {
                    $childModel->parentModel = $this;
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
        $relationalModelName = get_class($this);

        if (!$relationalRevision) {
            throw new ErrorException("No relational revisions found for " . get_class($this) . " model");
        }

        if (!$relationalRevision->parent_revision && !$relationalRevision->child_revision) {
            throw new ErrorException("No relational revisions found for " . get_class($this) . " model");
        }

        while ($relationalRevision->parent_revision) {
            $relationalModelName = $relationalRevision->parent_revision['model_name'];
            $relationalRevision = $this->getTargetRevision($relationalRevision->parent_revision);
        }

        if (!$relationalRevision->child_revisions) {
            throw new ErrorException("Child revisions not found for " . get_class($relationalModel) . " model");
        }

        $relationalModel = (new $relationalModelName())->where($relationalRevision->model_identifier)->first();
        $relationalModel->rollback($relationalRevision->id, $saveAsRevision);
    }

    /**
     * Get the target model from the revision
     *
     * @param array $revisionInfo
     * @return mixed
     */
    public function getTargetRevision($revisionInfo)
    {
        $modelName = $revisionInfo['model_name'];
        return (new $modelName())->getRevisionModel()->find($revisionInfo['revision_id']);
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
        return $this->allRevisions();
        //     ->where(function ($query) {
        //     $query->where('parent_revision', '!=', '')
        //         ->orWhere('child_revisions', '!=', '');
        // });
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
     * An accessor to get the self revision identifier
     *
     * @return mixed
     */
    public function getSelfRevisionIdentifierAttribute()
    {
        return [
            'revision_id' => $this->createdRevision->id,
            'model_name' => get_class($this)
        ];
    }

    /**
     * An accessor to get the parent revision identifier
     *
     * @return mixed
     */
    public function getParentRevisionIdentifierAttribute()
    {
        return [
            'revision_id' => $this->parentModel->createdRevision->id,
            'model_name' => get_class($this->parentModel)
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