<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 12/12/2019
 * Time: 11:00 AM
 */

namespace LuminateOne\RevisionTracking\Classes;


class EloquentDeleted
{
    public static function handle($model){

        if (!$model->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        if(config('revision_tracking.remove_on_delete', true)){

            $revisionModel = $model->getRevisionModel();

            $whereClause = [];

            if($model->revisionMode() === 0){
                $whereClause['model_name'] = get_class($model);
            }

            $whereClause['revision_identifiers'] = serialize([$model->getKeyName() => $model->getKey()]);

            $revisionModel->where($whereClause)->delete();
        }
    }
}