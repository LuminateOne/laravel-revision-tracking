<?php
namespace LuminateOne\RevisionTracking\Classes;

class EloquentDiff
{
    /**
     * Get the original values of the changed values
     * Loop through the changed values
     * Use the key in changed values to get the original values
     * @param $model
     * @return array
     */
    public static function get($model)
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
}