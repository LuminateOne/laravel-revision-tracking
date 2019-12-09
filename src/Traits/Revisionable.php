<?php
namespace LuminateOne\RevisionTracking\Traits;

use LuminateOne\RevisionTracking\Classes\Initialise;

trait Revisionable
{

    /**
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::updated(function ($model) {
            $model->trackChanges();
        });

        static::deleted(function ($model) {
            $model->trackChanges();
        });
    }

    /**
     * Start to track the changes
     */
    public function trackChanges()
    {
        // Initialise the Model,
        // get the revision table name,
        $this->createRevisionTableName();

    }

    /**
     * Create the revision table name, with the prefix declared in the config file
     */
    private function createRevisionTableName(){
        $this->revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
    }
}