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
        $this->getRevisionTableName();

        // get the primary key or unique key of the record whit values

    }

    /**
     * Create the revision table name, with the prefix declared in the config file
     * @param $model
     */
    private function getRevisionTableName(){
        $this->revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
    }
}