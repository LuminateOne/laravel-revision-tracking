<?php
namespace LuminateOne\RevisionTracking;

use Illuminate\Database\Eloquent\Model;
use ErrorException;

/**
 * This class can find and store the diff of a model
 *
 * @package     LuminateOne\RevisionTracking\Providers
 */
class RevisionTracking
{
    /**
     * Loop through the changed values
     * Use the field name in changed values to get the original values
     *
     * @param  Model $model             The model will be tracked
     *
     * @return array $originalFields    A key => value pair array, which stores the fields and the original values
     */
    public static function eloquentDiff($model)
    {
        $originalFields = [];

        $changes = $model->getChanges();
        $original = $model->getOriginal();

        foreach ($changes as $key => $value) {
            $aOriginalValue = [
                "value" => $original[$key],
                "column" => $key
            ];

            array_push($originalFields, $aOriginalValue);
        }

        return $originalFields;
    }

    /**
     * Get the primary key of the record,
     * Then store the original values as a serialized format
     * And store the primary field name and value in the revision table as a serialized format
     *
     * @param Model $model            The changes will be stored for
     * @param array $originalFields   A key => value pair array, which stores the field names and the original values
     */
    public static function eloquentStoreDiff($model, $originalFields)
    {
        $revisionModel = $model->getRevisionModel();

        if($model->revisionMode() === 'all'){
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifier = $model->revisionIdentifier();
        $revisionModel->original_values = $originalFields;

        $revisionModel->save();
    }

    /**
     * Delete the revision or not when a model is deleted
     * It depends on the "remove_on_delete" value in the config file
     *
     * @param Model $model A Eloquent model
     *
     * @throws ErrorException If the model cannot be found
     */
    public static function eloquentDelete($model)
    {
        if (config('revision_tracking.remove_on_delete', true)) {
            $revisionModel = $model->getRevisionModel();

            $targetRevisions = $revisionModel->where('revision_identifier', $model->revisionIdentifier(true));

            if ($model->revisionMode() === 'all') {
                $targetRevisions = $targetRevisions->where('model_name', get_class($model));
            }

            $targetRevisions->delete();
        }
    }

    /**
     * Restoring the revision.
     * Use the model name and the revision ID provided to retrieve the revision for the model
     *
     * @param string   $modelName   A model name that the revision will be restored for
     * @param integer  $revisionID  Revision ID for the model
     *
     * @throws ErrorException  If the model or the revision cannot be found
     */
    public static function eloquentRestore($modelName, $revisionID = null)
    {
        if (!class_exists($modelName)) {
            throw new ErrorException("The model: " . $modelName . ' does not exists, look like you changed the model name.');
        }

        $targetModel = new $modelName();

        $revisionModel = $targetModel->getRevisionModel();

        $targetRevision = null;

        // There are two revision modes, so we need to check the mode to see if we need to set the "model_name" in the query.
        if ($targetModel->revisionMode() === 0) {
            $targetRevision = $revisionModel->where(['model_name' => get_class($targetModel)]);
        } else {
            // When set table dynamically, the Model::all() does not work, so using "where id > -1" as a work around.
            $targetRevision = $revisionModel->where('id', '>', '-1');
        }

        // We keep filter the revision data, if there is a revision ID provided,
        if (!$revisionID) {
            $targetRevision = $targetRevision->latest('id')->first();
        } else {
            $targetRevision = $targetRevision->where(['id' => $revisionID])->first();
        }

        if (!$targetRevision) {
            throw new ErrorException("No revisions found for Model: " . get_class($targetModel));
        }

        $targetRecord = $targetModel->where($targetRevision->revision_identifiers)->first();

        if (!$targetRecord) {
            throw new ErrorException('The target record for the model: ' . get_class($targetModel) .
                ' could not be found. There are five possible reasons: 1. Table name changed. 2. Model name changed. 3. The record has been deleted. 4. Not restoring revision from the latest one. 5. The primary key has been changed'
            );
        }

        foreach ($targetRevision->original_values as $key => $value) {
            $targetRecord[$value['column']] = $value['value'];
        }

        $targetRecord->save();
    }
}