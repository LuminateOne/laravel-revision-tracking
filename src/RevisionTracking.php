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
     * @param Model $model The changes will be stored for
     * @param array $originalFields A key => value pair array, which stores the field names and the original values
     *
     * @return Revision The newly created revision model
     */
    public static function eloquentStoreDiff($model, $originalFields)
    {
        $revisionModel = $model->getRevisionModel();

        if ($model->revisionMode() === 'all') {
            $revisionModel->model_name = get_class($model);
        }

        if($model->relatedRevision){
            $revisionModel->related_revision = $model->relatedRevision;
        }

        $revisionModel->revision_identifier = $model->revisionIdentifier();
        $revisionModel->original_values = $originalFields;
        $revisionModel->save();

        return $revisionModel;
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
}