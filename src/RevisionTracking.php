<?php
namespace LuminateOne\RevisionTracking;

class RevisionTracking
{
    /**
     * Get the original values and changed values
     * Loop through the changed values
     * Use the key in changed values to get the original values
     *
     * @param $model    An Eloquent Model, the Model will be tracked
     * @return array    A key => value pair array, which stores the fields and the original values
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
     * Store the primary key name and value in the revision table as serialized format
     * Store the original value of changed value as as serialized format
     *
     * @param $model            An Eloquent Model, the changes will be stored for the Model
     * @param $originalFields   A key => value pair array, which stores the fields and the original values
     */
    public static function eloquentStoreDiff($model, $originalFields)
    {
        $revisionIdentifier = [$model->getKeyName() => $model->getKey()];

        $revisionModel = $model->getRevisionModel();

        if($model->revisionMode() === 'all'){
            $revisionModel->model_name = get_class($model);
        }

        $revisionModel->revision_identifier = serialize($revisionIdentifier);
        $revisionModel->original_values = serialize($originalFields);

        $revisionModel->save();
    }
}