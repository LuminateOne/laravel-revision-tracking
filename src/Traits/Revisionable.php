<?php

namespace LuminateOne\Revisionable\Traits;

use LuminateOne\Revisionable\Classes\Initialise;

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
        // get the primary key or unique key of the record whit values
        Initialise::ini($this);

    }
}