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
     * Get the model for the revision with the correct table by checking the revision mode
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
     * A function to create the revision identifier
     *
     * @param boolean $serialize Serialize the revision identifier or not
     * @return mixed
     */
    public function revisionIdentifier($serialize = false){
        $revisionIdentifier = [$this->getKeyName() => $this->getKey()];

        if($serialize){
            return serialize($revisionIdentifier);
        }

        return $revisionIdentifier;
    }
}