<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 9/12/2019
 * Time: 1:05 PM
 */

namespace LuminateOne\Revisionable\Classes;

use Log;
use DB;

class Initialise
{
    /**
     * Initialise the Mode
     * @param $model
     */
    public static function ini(&$model){
        self::getRevisionTableName($model);

        self::getPrimaryOrUniqueKey($model);
    }

    /**
     * Creat the revision table name
     * @param $model
     */
    private static function getRevisionTableName(&$model){
        $model->revision_table = config('revisionable.revision_table_prefix') . $model->getTable();
    }

    /**
     * Check if the model has the unique key
     * Set the unique key to a new variable
     * @param $model
     * @return bool
     */
    private static function getPrimaryOrUniqueKey(&$model){
        $revision_identifiers = [];

        // Check the primary key
        if($model->getKeyName()){
            $revision_identifiers[$model->getKeyName()] = $model->getKey();
            // array_push($keys, $model->getKeyName());
        }

        // If this Model does not have a primary key, try to get the unique keys
        if(empty($revision_identifiers)){
            $index = DB::select(DB::raw('SHOW INDEX FROM ' . $model->getTable()));

            foreach($index as $aIndex){
                $revision_identifiers[$aIndex->Column_name] = $model->attributes[$aIndex->Column_name];
                // array_push($keys, $aIndex->Column_name);
            }
        }

        $model->revision_identifiers = $revision_identifiers;
        return !empty($model->revision_identifiers);
    }
}