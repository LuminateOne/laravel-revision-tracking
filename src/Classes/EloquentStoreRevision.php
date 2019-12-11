<?php

namespace LuminateOne\RevisionTracking\Classes;

use LuminateOne\RevisionTracking\Models\RevisionsVersion;
use LuminateOne\RevisionTracking\Models\SingleRevisionModel;

class EloquentStoreRevision
{
    public static function save ($model, $originalValuesChanged){
        $revisionMode = config('revision_tracking.mode', 0);

        $revision_identifiers = [$model->getKeyName() => $model->getKey()];

        if($revisionMode === 0){
            // Create a new revision
            $newRevisionVersion = RevisionsVersion::create([
                'model_name' => get_class($model),
                'revision_identifiers' => serialize($revision_identifiers),
                'original_values' => serialize($originalValuesChanged)
            ]);
        }else {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $model->getTable();

            $singleRevisionModel = new SingleRevisionModel();
            $singleRevisionModel->setTable($revisionTableName);
            $singleRevisionModel->createTableIfNotExist();

            $singleRevisionModel->revision_identifiers = serialize($revision_identifiers);
            $singleRevisionModel->original_values = serialize($originalValuesChanged);

            $singleRevisionModel->save();
        }
    }
}