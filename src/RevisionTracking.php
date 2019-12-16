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
     * @param Model     $model          The Eloquent Model will be tracked after the attribute value changed
     * @return array    originalFields  An array of changed field name and the original values (key => value) pair
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
     * Get the primary key of the record, store it in the revision table as serialized format
     * Store the original value of changed value as as serialized format
     * If the the revision Mode is set to 0, insert the current Model name as "model_name" in the revision table
     *
     * @param Model $model            The Eloquent Model that the revision will be stored for
     * @param array $originalFields   An array of changed field name and the original values (key => value) pair
     */
    public static function eloquentStoreDiff($model, $originalFields)
    {
        $revisionModel = $model->getRevisionModel();

        if($model->revisionMode() === 'all'){
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifier = [$model->getKeyName() => $model->getKey()];
        $revisionModel->original_values = $originalFields;

        $revisionModel->save();
    }

    /**
     * Delete the revision or not when a Model is deleted
     * Depends on the "remove_on_delete" variable in the config file
     *
     * @param Model $model      The Eloquent Model
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

            $whereClause['revision_identifier'] = serialize([$model->getKeyName() => $model->getKey()]);

            $revisionModel->where($whereClause)->delete();
        }
    }
}