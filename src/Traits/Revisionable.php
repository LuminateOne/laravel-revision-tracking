<?php

namespace LuminateOne\Revisionable\Traits;

use LuminateOne\Revisionable\Classes\Initialise;
use Log;
use LuminateOne\Revisionable\Classes\EloquentChecker;
use LuminateOne\Revisionable\Classes\EloquentDiff;

trait Revisionable
{

    /**
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
<<<<<<<
        static::created(function ($model) {
            $model->trackChanges('created');
        });

=======

>>>>>>>
        static::updated(function ($model) {
            $model->trackChanges('updated');
        });

        static::deleted(function ($model) {
            $model->trackChanges('deleted');
        });
    }


    /**
     * @param $action
     */
    public function trackChanges($action)
    {
        Log::info('action: ' . $action);

        // Initialise the Model
        // Initialise the Model,
        // get the revision table name,
        // get the primary key or unique key of the record whit values
        Initialise::ini($this);

        Log::info('Initialised');

        Log::info($this->getRevisionTableName());

        Log::info($this->getRevisionIdentifiers());

        if($this->checkModel()){

            Log::info('checkModel: succeed');

            $revision_table_name = $this->getRevisionTableName();

            $originalValuesChanged = EloquentDiff::track($this);

            Log::info(print_r($originalValuesChanged, true));

        }
    }

    /**
     * Get the revision table name
     * @return string
     */
    public function getRevisionTableName()
    {
        return $this->revision_table;
    }

    /**
     * Get primary key or unique keys
     * @return mixed
     */
    public function getRevisionIdentifiers()
    {
        return $this->revision_identifiers;
    }

    /**
     * Get the value of the primary key or the unique key
     * @return array
     */
    // public function getRevisionIdentifiers(){
    //     $revision_identifiers = [];
    //
    //     $unique_keys = $this->getPrimaryOrUniqueKey();
    //
    //     foreach ($unique_keys as $aUniqueKey){
    //         $revision_identifiers[$aUniqueKey] = $this->attributes[$aUniqueKey];
    //     }
    //
    //     return $revision_identifiers;
    // }

    /**
     * Check if the model has a table for revision and primary key or unique key
     * @return bool
     */
    public function checkModel()
    {
        if (!$this->getRevisionTableName()) {
            Log::warning(print_r($this->getTable() . " does not revision table.", true));
            return false;
        }

        if (!$this->getRevisionIdentifiers()) {
            Log::warning(print_r($this->getTable() . " does not have unique keys.", true));
            return false;
        }
        return true;
    }
}