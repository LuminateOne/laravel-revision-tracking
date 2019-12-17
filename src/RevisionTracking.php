<?php
namespace LuminateOne\RevisionTracking;

use Illuminate\Database\Eloquent\Model;
use ErrorException;

class RevisionTracking
{
    /**
     * Loop through the changed values
     * Use the field name in changed values to get the original values
     *
     * @param  Model $model             The Model will be tracked
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
     * Store the primary key name and value as serialized format
     * Store the original value of changed value as as serialized format
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

        $revisionModel->revision_identifier = [$model->getKeyName() => $model->getKey()];
        $revisionModel->original_values = $originalFields;

        $revisionModel->save();
    }

    /**
     * Delete the revision or not when a Model is deleted
     * Depends on the "remove_on_delete" value in the config file
     *
     * @param Model $model The Eloquent Model
     *
     * @throws ErrorException If the Model cannot be found
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