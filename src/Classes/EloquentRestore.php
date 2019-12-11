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
     * @param $targetModelName
     * @throws ErrorException
     */
    public static function restore($targetModelName = null)
    {
        $latestRevision = null;
        if (!$targetModelName) {
            $latestRevision = RevisionsVersion::latest('id')->first();
        } else {
            $latestRevision = RevisionsVersion::where(['model_name' => $targetModelName])->latest('id')->first();
        }

        if (!$latestRevision) {
            Log::info("No revisions found for Model: " . $targetModelName);
            return;
        }

        $targetModelName = $latestRevision->model_name;

        if (!class_exists($targetModelName)) {
            throw new ErrorException('The target Model: ' . $targetModelName . ' does not exist, looks like you changed the model name.');
        }

        $targetModel = new $targetModelName();

        $targetRecord = $targetModel->where($latestRevision->revision_identifiers)->first();

        if (!$targetRecord) {
            throw new ErrorException('The target record for the Model: ' . $targetModelName .
                'not found. There are three possible reasons: ' .
                '1. Table name changed. ' .
                '2. Model name changed. ' .
                '3. The record has been deleted. ' .
                '4. Not restoring revision from the latest one.'
            );
        }

        foreach ($latestRevision->original_values as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->save();
    }
}