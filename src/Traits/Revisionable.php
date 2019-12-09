<?php
namespace LuminateOne\RevisionTracking\Traits;

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
        $revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
    }
}