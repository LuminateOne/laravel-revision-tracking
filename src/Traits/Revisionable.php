<?php
namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;
use LuminateOne\RevisionTracking\RevisionTracking;
use LuminateOne\RevisionTracking\Models\Revision;

trait Revisionable
{
    /**
     * Holds the parent revision information
     * @var array
     */
    public $rootRevision = null;

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
            // \Log::info(print_r("created", true));
            // \Log::info(print_r($model, true));
            $model->trackChanges();
        });

        static::updated(function ($model) {
            $model->trackChanges();

            \Log::info(print_r($model, true));
        });

        static::deleted(function ($model) {
            // \Log::info(print_r("deleted", true));
            // \Log::info(print_r($model, true));
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

        $this->addThisRevisionToChildRelation($revision);
        $this->addThisRevisionToParentRelation($revision);
    }

    /**
     * Get a specific revision by the revision ID.
     *
     * @param $revisionId       Revision ID for the model
     *
     * @return mixed            A single revision model
     * @throws ErrorException   If the revision table cannot be found
     */
    public function getRevision($revisionId)
    {
        return $this->allRevisions()->where('id', $revisionId)->first();
    }

    /**
     * Get all revisions for this model.
     *
     * @return mixed            A collection of revision model
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
     * Restoring the revision.
     * Using the model name and the revision ID provided to retrieve the revision for the model
     *
     * @param integer  $revisionId      Revision ID for the model
     * @param boolean  $saveAsRevision  true =>  save the “rollback” as a new revision of the model
     *                                  false => rollback to a specific revision and delete all the revisions that came after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollback($revisionId, $saveAsRevision = true)
    {
        $targetRevision = $this->allRevisions()->where('id', $revisionId)->first();

        if (!$targetRevision) {
            throw new ErrorException("No revisions found for " . get_class($this) . " model");
        }

        if(empty($targetRevision->original_values)){
            $this->delete();
        } else {
            foreach ($targetRevision->original_values as $key => $value) {
                $this[$key] = $value;
            }
            $this->save();
        }

        if(!$saveAsRevision){
            $this->allRevisions()->where('id', '>=', $revisionId)->delete();
        }
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
     * Read the config to get the current revision mode
     *
     * @return string Represents the revision mode
     */
    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }

    /**
     * A function to create the model identifier
     *
     * @param boolean $serialize Serialize the model identifier or not
     * @return mixed
     */
    public function modelIdentifier($serialize = false){
        $modelIdentifier = [$this->getKeyName() => $this->getKey()];

        if($serialize){
            return serialize($modelIdentifier);
        }

        return $modelIdentifier;
    }

    /**
     * Add this revision to each of its child models as parent revision
     *
     * @param Revision $revision The newly created revision of the model
     *
     * @return mixed
     */
    public function addThisRevisionToChildRelation($revision){
        foreach ($this->relations as $relations) {
            $relations = $relations instanceof Collection ? $relations->all() : [$relations];

            foreach (array_filter($relations) as $aRelation) {
                if($aRelation->rootRevision){
                    return;
                }

                if($aRelation->usingRevisionableTrait){
                    $aRelation->rootRevision = $revision;
                    $aRelation->addThisRevisionToChildRelation($revision);
                }
            }
        }
    }

    /**
     * Add the this revision to its parent revision as child revision
     *
     * @param Revision $revision The newly created revision id of the model
     */
    public function addThisRevisionToParentRelation($revision){
        if(!$this->rootRevision){
            return;
        }

        $childRevision = $this->rootRevision->child_revisions;
        if(!$childRevision){
            $childRevision = [];
        }
        array_push($childRevision, ['id' => $revision->id, 'model_name' => get_class($this)]);
        $this->rootRevision->child_revisions = $childRevision;

        $this->rootRevision->save();
    }
}