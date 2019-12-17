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

    public function trackChanges()
    {
        if (!$this->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        $originalValuesChanged = RevisionTracking::eloquentDiff($this);

        RevisionTracking::eloquentStoreDiff($this, $originalValuesChanged);
    }


    /**
     * Get a specific revision by the revision ID.
     *
     * @param $revisionId
     * @return mixed
     * @throws ErrorException
     */
    public function getRevision($revisionId)
    {
        return $this->allRevisions()->where(['id' => $revisionId])->first();
    }

    /**
     * Get all revisions for this Model.
     *
     * @return mixed            A collection of revision Model
     * @throws ErrorException
     */
    public function allRevisions()
    {
        $targetRevision = null;

        $whereClause = [['revision_identifier', '=', serialize([$this->getKeyName() => $this->getKey()])]];

        //Check the revision mode to see if we need to add "model_name" in the where clause
        if ($this->revisionMode() === 'all') {
            array_push($whereClause, ['model_name', '=', get_class($this)]);
        }

        $targetRevision = $this->getRevisionModel()->where($whereClause);

        return $targetRevision;
    }

    /**
     * Restoring the revision.
     * Using the Model name and the revision ID provided to retrieve the revision for the Model
     *
     * @param      $revisionId      Revision ID for the Model
     * @param true $saveAsRevision  true =>  save the “rollback” as a new revision of the model.
     *                              false => rollback to a specific revision and delete all the revisions that came after that revision,
     * @throws ErrorException
     */
    public function rollback($revisionId, $saveAsRevision = true)
    {
        $targetRevision = $this->allRevisions()->where(['id' => $revisionId])->first();

        if (!$targetRevision) {
            throw new ErrorException("No revisions found for Model: " . get_class($this));
        }

        $targetRecord = $this->where($targetRevision->revision_identifiers)->first();

        if (!$targetRecord) {
            throw new ErrorException('The target record for the Model: ' . get_class($this) .
                ' could not be found. There are five possible reasons: ' .
                '1. Table name changed. ' . '2. Model name changed. ' . '3. The record has been deleted. ' . '4. Not restoring revision from the latest one.' . '5. The primary key has been changed'
            );
        }

        foreach ($targetRevision->original_values as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->save();

        if(!$saveAsRevision){
            $this->allRevisions()->where([['id', '>=', $revisionId]])->delete();
        }
    }


    /**
     * Check the current Revision Mode and
     * get the corresponding Eloquent Model for the revision table
     *
     * @throws ErrorException
     * @return Revision
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
            throw new ErrorException('The revision table for the Model: ' . get_class($this) .
                ' could not be found. There are three possible reasons: ' . '1. Table name changed. ' . '2. Model name changed. ' .
                '3. Did not run "php artisan table:revision ' . get_class($this) . '" command.'
            );
        }

        $revisionModel = new Revision();
        $revisionModel->setTable($revisionTableName);

        return $revisionModel;
    }

    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }
}