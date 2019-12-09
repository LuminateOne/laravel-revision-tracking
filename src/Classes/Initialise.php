<?php
namespace LuminateOne\Revisionable\Classes;

use Log;

class Initialise
{
    /**
     * Initialise the Mode,
     * Create the revision table name
     * Get the primary key or the unique key for the record
     * @param $model
     */
    public static function ini(&$model){
        self::checkRevisionTableName($model);
    }

    /**
     * Create the revision table name, with the prefix declared in the config file
     * @param $model
     */
    private static function checkRevisionTableName(&$model){
        $model->revision_table = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();
    }
}