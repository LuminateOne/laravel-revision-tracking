<?php
namespace LuminateOne\RevisionTracking;

use Illuminate\Database\Eloquent\Model;

class RevisionTracking
{
    /**
     * Loop through the changed values
     * Use the field name in changed values to get the original values
     *
     * @param   Model $model            The Model will be tracked
     *
     * @return  array $originalFields   A key => value pair array, which stores the fields and the original values
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
     * Store the original values as serialized format
     * Store the primary field name and value in the revision table as serialized format
     *
     * @param Model $model            The changes will be stored for
     * @param array $originalFields   A key => value pair array, which stores the field names and the original values
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