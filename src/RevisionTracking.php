<?php
namespace LuminateOne\RevisionTracking;

use ErrorException;

class RevisionTracking
{
    /**
     * Get the original values of the changed values
     * Loop through the changed values
     * Use the key in changed values to get the original values
     *
     * @param $model
     * @return array
     */
    public static function eloquentDiff($model)
    {
        $originalValuesChanged = [];

        $changes = $model->getChanges();
        $original = $model->getOriginal();

        foreach ($changes as $key => $value) {
            $aOriginalValue = [
                "value" => $original[$key],
                "column" => $key
            ];

            array_push($originalValuesChanged, $aOriginalValue);
        }

        return $originalValuesChanged;
    }

    /**
     * Get the primary key of the record, store it in the revision table as serialized format
     * Store the original value of changed value as as serialized format
     * If the the revision Mode is set to 0, insert the current Model name as "model_name" in the revision table
     *
     * @param $model
     * @param $originalValuesChanged
     */
    public static function eloquentStoreDiff($model, $originalValuesChanged)
    {
        $revisionIdentifier = [$model->getKeyName() => $model->getKey()];

        $revisionModel = $model->getRevisionModel();

        if ($model->revisionMode() === 0) {
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifier = serialize($revisionIdentifier);
        $revisionModel->original_values = serialize($originalValuesChanged);

        $revisionModel->save();
    }

    /**
     * Delete the revision or not when a Model is deleted
     * Delete the revisions if the "remove_on_delete" is set to true in the config file
     *
     * @param $model
     * @throws ErrorException
     */
    public static function eloquentDelete($model)
    {
        if (!$model->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        if (config('revision_tracking.remove_on_delete', true)) {
            $revisionModel = $model->getRevisionModel();

            $whereClause = [];

            if ($model->revisionMode() === 0) {
                $whereClause['model_name'] = get_class($model);
            }

            $whereClause['revision_identifiers'] = serialize([$model->getKeyName() => $model->getKey()]);

            $revisionModel->where($whereClause)->delete();
        }
    }

    /**
     *  Restoring the revision.
     *
     * @param $targetModelName
     * @param null $revisionID
     * @throws ErrorException
     */
    public static function eloquentRestore($targetModelName, $revisionID = null)
    {
        if (!class_exists($targetModelName)) {
            throw new ErrorException("The Model: " . $targetModelName . ' does not exists, look like you changed the Model name.');
        }

        $targetModel = new $targetModelName();

        $revisionModel = $targetModel->getRevisionModel();

        $targetRevision = null;

        // This step is to get all the revisions for the given Model, or we can call it as filtering revision
        // Since we are loading the Eloquent Model for the revision table dynamically ($targetModel->revisionMode()),
        // we have to check revision Mode, because they have the different table structure
        //
        // if the Mode is set to 0 (Put all the revisions into a single table),
        // when query the revision we have to include the "model_name" in where clause
        //
        // if the Mode is not set to 0,
        // (Each Model will have its own revision table and we set the table name dynamically to tell the Eloquent Model which table to use),
        // The method $revisionModel->all() is not working properly when set table name dynamically,
        // It will call the static ::all() method
        // then where id > -1 do the trick to fetch all the revisions for the corresponding Model
        if ($targetModel->revisionMode() === 0) {
            $targetRevision = $revisionModel->where(['model_name' => get_class($targetModel)]);
        } else {
            $targetRevision = $revisionModel->where('id', '>', '-1');
        }

        // If there is a revision ID provided, we continually filter the revision data
        if (!$revisionID) {
            $targetRevision = $targetRevision->latest('id')->first();
        } else {
            $targetRevision = $targetRevision->where(['id' => $revisionID])->first();
        }

        if (!$targetRevision) {
            \Log::info("No revisions found for Model: " . get_class($targetModel));
            return;
        }

        $targetRecord = $targetModel->where($targetRevision->revision_identifiers)->first();

        if (!$targetRecord) {
            throw new ErrorException('The target record for the Model: ' . get_class($targetModel) .
                ' could not be found. There are five possible reasons: ' .
                '1. Table name changed. ' . '2. Model name changed. ' . '3. The record has been deleted. ' . '4. Not restoring revision from the latest one.' . '5. The primary key has been changed'
            );
        }

        foreach ($targetRevision->original_values as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->save();
    }
}