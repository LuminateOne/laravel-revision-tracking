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

        $revision = RevisionTracking::eloquentStoreDiff($this, $originalFields);

        $this->addThisRevisionToChildRelation($revision, $this);
        $this->addThisRevisionToParentRelation($revision);
    }

    /**
     * Add this revision to each of its child models as parentRevision,
     *
     * @param Revision $revision The newly created revision of the model
     * @param Model $model The model`s relations that the revision will be assigned to
     */
    public function addThisRevisionToChildRelation($revision, $model)
    {
        foreach ($model->relations as $relations) {
            $relations = $relations instanceof Collection ? $relations->all() : [$relations];

            foreach (array_filter($relations) as $aRelation) {
                if ($aRelation->usingRevisionableTrait) {
                    $aRelation->parentRevision = $revision;
                } else {
                    // If the current relation is not using the Revisionable Trait, then we need to go deeper to find its child relations,
                    // We need to always use `$this` to call the addThisRevisionToChildRelation recursively,
                    // because when a model without the revision control turned on involved,
                    // it will break the recursive loop
                    $this->addThisRevisionToChildRelation($revision, $aRelation);
                }
            }
        }
    }

    /**
     * Add the this revision to its parent revision as child revision
     *
     * @param Revision $revision The newly created revision id of the model
     */
    public function addThisRevisionToParentRelation($revision)
    {
        if (!$this->parentRevision) {
            return;
        }

        $childRevision = $this->parentRevision->child_revisions;
        if (!$childRevision) {
            $childRevision = [];
        }
        array_push($childRevision, $revision->revisionIdentifier());
        $this->parentRevision->child_revisions = $childRevision;

        $this->parentRevision->save();
    }

    /**
     * Restoring the revision.
     * Using the model name and the revision ID provided to retrieve the revision for the model
     *
     * @param integer $revisionId Revision ID for the model
     * @param boolean $saveAsRevision true =>  save the “rollback” as a new revision of the model
     *                                  false => rollback to a specific revision and delete all the revisions that came
     *                                           after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollback($revisionId, $saveAsRevision = true)
    {
        $targetRevision = $this->getRevision($revisionId);

        if (!$targetRevision) {
            throw new ErrorException("No revisions found for " . get_class($this) . " model");
        }

        if (empty($targetRevision->original_values)) {
            $this->delete();
        } else {
            foreach ($targetRevision->original_values as $key => $value) {
                $this[$key] = $value;
            }
            $this->save();
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
     *                                  false => rollback to a specific revision and delete all the revisions that came
     *                                           after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollbackWithRelation($revisionId, $saveAsRevision = true)
    {
        $parentRvision = $this->getRevision($revisionId);

        if (!$parentRvision) {
            throw new ErrorException("No revisions found for " . get_class($this) . " model");
        }

        if(!$parentRvision->parent_revision && !$parentRvision->child_revision){
            throw new ErrorException("No relational revisions found for " . get_class($this) . " model");
        }

        // $parentRvision = $targetRevision->parent_revision;
        while($parentRvision->parent_revision){
            $parentRvisionInfo = $parentRvision->parent_revision;
            $parentRevisionModel = (new $parentRvisionInfo['model_name'])();
            $parentRvision = $parentRevisionModel->getRevision($parentRvisionInfo['id']);
        }
        // if (empty($targetRevision->original_values)) {
        //     $this->delete();
        // } else {
        //     foreach ($targetRevision->original_values as $key => $value) {
        //         $this[$key] = $value;
        //     }
        //     $this->save();
        // }
        //
        // if (!$saveAsRevision) {
        //     $this->allRevisions()->where('id', '>=', $revisionId)->delete();
        // }
    }

    /**
     * Get a specific revision by the revision ID.
     *
     * @param $revisionId       Revision ID for the model
     *
     * @return Revision            A single revision model
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
     * Get the Model for the revision with the correct table by checking the revision mode
     *
     * @throws ErrorException   If the revision table cannot be found
     *
     * @return Revision         An Eloquent model for the revision
     */
    public function getRevisionModel()
    {
        $revisionTableName = null;
        if ($this->revisionMode() === 'all') {
            $revisionTableName = 'revisions';
        } else {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
        }

        if (!Schema::hasTable($revisionTableName)) {
            throw new ErrorException('The revision table for the model: ' . get_class($this) .
                ' could not be found. There are three possible reasons: 1. Table name changed. 2. Model name changed. 3. Did not run "php artisan table:revision ' . get_class($this) . '" command.'
            );
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
     * Read the config to get the current revision mode
     *
     * @return string Represents the revision mode
     */
    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }
}