<?php
namespace LuminateOne\RevisionTracking\Traits;

use Log;
use DB;
use LuminateOne\RevisionTracking\Classes\Initialise;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;

trait Revisionable
{

    /**
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
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

        // Initialise the Model,
        // get the revision table name,
        $this->createRevisionTableName();

        // get the revision table name,
        $this->getRevisionIdentifiers();

        if(empty($this->revision_identifiers)){
            Log::warning(print_r($this->getTable() . " does not have unique keys.", true));
        }

        if(!empty($this->revision_identifiers)){

            $originalValuesChanged = EloquentDiff::get($this);

            // Log::info(print_r($originalValuesChanged, true));

        }
    }

    /**
     * Create the revision table name, with the prefix declared in the config file
     */
    private function createRevisionTableName(){
        $this->revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
    }


    /**
     * Check if the model has primary key
     * Check the Model has the unique key, if the Model does not have a primary key
     * Save the unique keys and value to a new variable
     * @return bool
     */
    private function getRevisionIdentifiers(){
        $revision_identifiers = [];

        // Check the primary key
        if($this->getKeyName()){
            $revision_identifiers[$this->getKeyName()] = $this->getKey();
        }

        // If this Model does not have a primary key, try to get the unique keys and values
        if(empty($revision_identifiers)){
            $index = DB::select(DB::raw('SHOW INDEX FROM ' . $this->getTable()));

            foreach($index as $aIndex){
                $revision_identifiers[$aIndex->Column_name] = $this->attributes[$aIndex->Column_name];
            }
        }

        $this->revision_identifiers = $revision_identifiers;
    }
}