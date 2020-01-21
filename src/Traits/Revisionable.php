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
     * @var Model
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
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::created(function ($model) {
            $model->trackChanges();
        });

        static::updated(function ($model) {
            $model->trackChanges();
        });

        static::deleted(function ($model) {
            $model->trackChanges();
            RevisionTracking::eloquentDelete($model);
        });
    }

    /**
     * Set relational revision manually, sometimes the parent model will not be changed (updated, created, deleted),
     * So the parent model will never trigger any event in this Revisionable Trait, so we need to call this
     * method manually to set this model as the parentModel to its all child models, so when the child
     * model is changed, the child model can create a revision for its parent model,
     * and set the relation between revisions
     */
    public function setAsRelationalRevision()
    {
        $this->addThisModelToItsChildModels($this, $this);
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

        $this->addThisModelToItsChildModels($this, $this);
        $this->updateParentRevision();
    }

    /**
     * Add this model to each of its child models as parentModel, it will go deeper if the
     * child model does not have the revision control turned on
     *
     * @param Model $currentModel The model`s relations that the revision will be assigned to
     * @param Model $parentModel parentModel
     */
    public function addThisModelToItsChildModels($currentModel, $parentModel)
    {
        foreach ($currentModel->relations as $relations) {
            $relations = $relations instanceof Collection ? $relations->all() : [$relations];

            foreach (array_filter($relations) as $aRelation) {
                if ($aRelation->usingRevisionableTrait) {
                    if($aRelation->parentModel){
                        return;
                    }
                    $aRelation->parentModel = $parentModel;
                }
                // If the current relation is not using the Revisionable Trait, then we need to go deeper to find its child relations,
                // We need to always use `$this` to call the addThisModelToItsChildModel recursively,
                // because when a model without the revision control turned on involved,
                // it will break the recursive loop
                $this->addThisModelToItsChildModels($aRelation, $parentModel);
            }
        }
    }

    /**
     * Update parent revision and self revision
     */
    public function updateParentRevision()
    {
        if (!$this->parentModel) {
            return;
        }

        if (!$this->parentModel->createdRevision) {
            RevisionTracking::eloquentStoreDiff($this->parentModel, null);
        }

        $this->parentModel->createdRevision->addChildRevision($this->self_revision_identifier);
    }

    /**
     * Restoring the revision.
     * Using the revision ID provided to retrieve the revision for the model
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
                $targetModel = new $modelName();
                $revision = $targetModel->getRevisionModel()->find($revisionInfo['revision_id']);;
                $childModel = $targetModel->where($revision->model_identifier)->first();

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
        return $this->allRevisions()->where('revisions', 'REGEXP', 's:[0-9]+:"child";a:[0-9]+:');
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
     * Read the config to get the current revision mode
     *
     * @return string Represents the revision mode
     */
    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }
}