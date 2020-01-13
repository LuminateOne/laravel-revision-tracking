<?php
namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use Illuminate\Support\Facades\Schema;
use LuminateOne\RevisionTracking\RevisionTracking;
use LuminateOne\RevisionTracking\Models\Revision;

trait Revisionable
{
    /**
     *  Catch the updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::updated(function ($model) {
            $model->trackChanges();
        });

        static::deleted(function ($model) {
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
    }

    /**
     * Get a specific revision by the revision ID.
     *
     * @param $revisionId       Revision ID for the Model
     *
     * @return mixed            A single revision Model
     * @throws ErrorException   If the revision table cannot be found
     */
    public function getRevision($revisionId)
    {
        return $this->allRevisions()->where(['id' => $revisionId])->first();
    }

    /**
     * Get all revisions for this Model.
     *
     * @return mixed            A collection of revision Model
     * @throws ErrorException   If the revision table cannot be found
     */
    public function allRevisions()
    {
        $targetRevision = null;

        // Check the revision mode to see if we need to filter "model_name"
        if ($this->revisionMode() === 'all') {
            $modelName = get_class($this);
            $targetRevision = $this->getRevisionModel()->where('model_name', $modelName);
        } else {
            $targetRevision = $this->getRevisionModel()->where('id', '>', '-1');
        }

        $targetRevision = $targetRevision->where('revision_identifier', '=', serialize([$this->getKeyName() => $this->getKey()]));

        return $targetRevision;
    }

    /**
     * Restoring the revision.
     * Using the Model name and the revision ID provided to retrieve the revision for the Model
     *
     * @param integer  $revisionId      Revision ID for the Model
     * @param boolean  $saveAsRevision  true =>  save the “rollback” as a new revision of the model
     *                                  false => rollback to a specific revision and delete all the revisions that came after that revision
     *
     * @throws ErrorException  If the revision or the original record cannot be found
     */
    public function rollback($revisionId, $saveAsRevision = true)
    {
        $targetRevision = $this->allRevisions()->where(['id' => $revisionId])->first();

        if (!$targetRevision) {
            throw new ErrorException("No revisions found for Model: " . get_class($this));
        }

        foreach ($targetRevision->original_values as $key => $value) {
            $this[$value['column']] = $value['value'];
        }

        $this->save();

        if(!$saveAsRevision){
            $this->allRevisions()->where([['id', '>=', $revisionId]])->delete();
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
}