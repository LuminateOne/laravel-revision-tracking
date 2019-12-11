<?php

namespace LuminateOne\RevisionTracking\Classes;

use Log;
use ErrorException;

class EloquentRestore
{
    /**
     *  Restoring the revision.
     *
     * @param $targetModelName
     * @param null $revisionID
     * @throws ErrorException
     */
    public static function restore($targetModelName, $revisionID = null)
    {
        if(!class_exists($targetModelName)){
            throw new ErrorException("The Model: " . $targetModelName . ' does not exists, look like you changed the Model name.');
        }

        $targetModel = new $targetModelName();

        $revisionModel = $targetModel->getRevisionModel();

        $targetRevision = null;

        // Get all the revisions,
        // if the mode is 0, then get the revision with model_name
        // Else get all
        if($targetModel->revisionMode() === 0){
            // $whereClause['model_name'] = get_class($targetModel);
            $targetRevision = $revisionModel->where(['model_name' => get_class($targetModel)]);
        }else{
            // Get all is not working properly when set table name dynamically, where id > -1 do the trick
            $targetRevision = $revisionModel->where('id', '>', '-1');
        }

        // If no revision ID provided, get the latest one
        // Else get the revision with the id.
        if (!$revisionID) {
            $targetRevision = $targetRevision->latest('id')->first();
        }
        else {
            $targetRevision = $targetRevision->where(['id' =>$revisionID])->first();
        }

        if (!$targetRevision) {
            Log::info("No revisions found for Model: " . get_class($targetModel));
            return;
        }

        $targetRecord = $targetModel->where($targetRevision->revision_identifiers)->first();

        if (!$targetRecord) {
            throw new ErrorException('The target record for the Model: ' . get_class($targetModel) .
                ' not found. There are three possible reasons: ' .
                '1. Table name changed. ' .
                '2. Model name changed. ' .
                '3. The record has been deleted. ' .
                '4. Not restoring revision from the latest one.'
            );
        }

        foreach ($targetRevision->original_values as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->save();
    }
}