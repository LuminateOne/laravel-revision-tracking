<?php

namespace LuminateOne\RevisionTracking\Classes;

class EloquentStoreRevision
{
    public static function save ($model, $originalValuesChanged){

        $revision_identifiers = [$model->getKeyName() => $model->getKey()];

        $revisionModel = $model->getRevisionModel();

        if($model->revisionMode() === 0){
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifiers = serialize($revision_identifiers);
        $revisionModel->original_values = serialize($originalValuesChanged);

        $revisionModel->save();
    }
}