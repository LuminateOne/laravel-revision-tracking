<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 6/12/2019
 * Time: 8:40 AM
 */

namespace LuminateOne\Revisionable\Traits;

use LuminateOne\Revisionable\Classes\Initialise;
use Log;

trait Revisionable
{

    /**
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::created(function ($model) {
            Log::info('created');

            $model->trackChanges();

            Log::info(print_r($model, true));
        });

        static::updated(function ($model) {
            Log::info('updated');

            $model->trackChanges();

            Log::info(print_r($model, true));
        });

        static::deleted(function ($model) {
            Log::info('deleted');

            $model->trackChanges();

            Log::info(print_r($model, true));
        });
    }

    /**
     * Start to track the changes
     */
    public function trackChanges()
    {
        // Initialise the Model
        Initialise::ini($this);

        $revision_table_name = $this->getRevisionTableName();

        
        Log::info($this->getRevisionTableName());
    }

    /**
     * Get the revision table name
     * @return string
     */
    public function getRevisionTableName()
    {
        return $this->revision_table;
    }
}