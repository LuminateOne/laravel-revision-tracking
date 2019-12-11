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