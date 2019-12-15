<?php
namespace LuminateOne\RevisionTracking;

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
     * If the the revision Mode is set to 0, set the current Model name as "model_name" in the revision table
     *
     * @param $model
     * @param $originalValuesChanged
     */
    public static function eloquentStoreDiff($model, $originalValuesChanged)
    {
        $revisionIdentifier = [$model->getKeyName() => $model->getKey()];

        $revisionModel = $model->getRevisionModel();

        if($model->revisionMode() === 'all'){
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifier = serialize($revisionIdentifier);
        $revisionModel->original_values = serialize($originalValuesChanged);

        $revisionModel->save();
    }
}