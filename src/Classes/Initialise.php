<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 9/12/2019
 * Time: 1:05 PM
 */

namespace LuminateOne\Revisionable\Classes;

use Log;

class Initialise
{
    /**
     * Initialise the Mode
     * @param $model
     */
    public static function ini(&$model){
        self::checkRevisionTableName($model);
    }

    /**
     * Check if the Model has a custom revision table name
     * @param $model
     */
    private static function checkRevisionTableName(&$model){
        if(!$model->revision_table){
            $model->revision_table = config('revisionable.revision_table_prefix') . $model->getTable();
        }
    }
}