<?php

namespace LuminateOne\RevisionTracking\Classes;

use Log;
use Illuminate\Support\Facades\Schema;
use ErrorException;
use LuminateOne\RevisionTracking\Models\SingleModelRevision;
use LuminateOne\RevisionTracking\Models\RevisionsVersion;

class EloquentRestore
{

    /**
     *  Restoring the revision.
     *  Get the latest record from revisions_versions table.
     *  Get the latest record from the revision table.
     */
    public static function restore()
    {
        $latestRevisionVersion = RevisionsVersion::latest('id')->first();

        if(!$latestRevisionVersion){
            Log::info("No revisions found");
            return;
        }

        $targetModelName = $latestRevisionVersion->model_name;

        if(!class_exists($targetModelName)){
            throw new ErrorException('The target Model: ' . $targetModelName . ' does not exist, looks like you changed the model name.');
        }

        $targetModel = new $targetModelName();

        $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $targetModel->getTable();
        if(!Schema::hasTable($revisionTableName)){
            throw new ErrorException("No revisions found for " . $targetModelName . ', looks like you changed the model name or revision table name.');
        }

        $singleRevisionModel = new SingleModelRevision();
        $singleRevisionModel->setTable($revisionTableName);
        $singleLatestRevision = $singleRevisionModel->latest('id')->first();

        $ids = unserialize($singleLatestRevision->revision_identifiers);
        $oriValues = unserialize($singleLatestRevision->original_values);

        $targetRecord = $targetModel->where($ids)->first();

        foreach ($oriValues as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->append('is_restoring');
        $targetRecord->save();

        $latestRevisionVersion->delete();
        $singleLatestRevision->delete();
    }
}