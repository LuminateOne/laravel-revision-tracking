<?php
namespace LuminateOne\RevisionTracking\Classes;

use Log;
use DB;

class Initialise
{
    /**
     * Initialise the Model,
     * Create the revision table name
     * Get the primary key or the unique key for the record
     * @param $model
     * @return boolean
     */
    public static function ini(&$model){
        self::getRevisionTableName($model);

        if(!self::getRevisionIdentifiers($model)){
            Log::warning(print_r($model->getTable() . " does not have unique keys.", true));
            return false;
        }

        return true;
    }

    /**
     * Create the revision table name
     * @param $model
     */
    private static function getRevisionTableName(&$model){
        $model->revision_table = config('revisionable.revision_table_prefix') . $model->getTable();
    }

    /**
     * Check if the model has primary key
     * Check the Model has the unique key, if the Model does not have a primary key
     * Save the unique keys and value to a new variable
     * @param $model
     * @return bool
     */
    private static function getRevisionIdentifiers(&$model){
        $revision_identifiers = [];

        // Check the primary key
        if($model->getKeyName()){
            $revision_identifiers[$model->getKeyName()] = $model->getKey();
        }

        // If this Model does not have a primary key, try to get the unique keys and values
        if(empty($revision_identifiers)){
            $index = DB::select(DB::raw('SHOW INDEX FROM ' . $model->getTable()));

            foreach($index as $aIndex){
                $revision_identifiers[$aIndex->Column_name] = $model->attributes[$aIndex->Column_name];
            }
        }

        $model->revision_identifiers = $revision_identifiers;

        return !empty($model->revision_identifiers);
    }
}