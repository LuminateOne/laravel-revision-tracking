<?php
namespace LuminateOne\RevisionTracking;

use ErrorException;
use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Models\Revision;

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
        $aOriginalValue = [];

        $changes = $model->getChanges();
        $original = $model->getOriginal();

        // If changes is empty, then the action could be deletion or creation
        // then we return the original value, this will be used to
        // check what rollback action will be performed
        if (empty($changes)) {
            return $original;
        }

        foreach ($changes as $key => $value) {
            $aOriginalValue[$key] = $original[$key];
        }

        return $aOriginalValue;
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
    public static function eloquentStoreDiff($model, $originalFields = null)
    {
        $revisionModel = $model->getRevisionModel();

        // If current model is the top-level model, then it will use the
        // existing revision to store the changed values
        if(!$model->parentModel && $model->hasRelationLoaded() && $model->createdRevision){
            $revisionModel = $model->createdRevision;
        }

        if ($model->revisionMode() === 'all') {
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->model_identifier = $model->modelIdentifier();
        if($originalFields !== null){
            $revisionModel->original_values = $originalFields;
        }
        $revisionModel->save();

        $model->createdRevision = $revisionModel;
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

            $targetRevisions = $revisionModel->where('model_identifier', $model->modelIdentifier(true));

            if ($model->revisionMode() === 'all') {
                $targetRevisions = $targetRevisions->where('model_name', get_class($model));
            }

            $targetRevisions->delete();
        }
    }
}