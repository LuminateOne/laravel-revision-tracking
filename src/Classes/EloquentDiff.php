<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 9/12/2019
 * Time: 12:57 PM
 */

namespace LuminateOne\RevisionTracking\Classes;

use Log;

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
        Log::info(print_r($model, true));

        $changes = $model->getChanges();
        $original = $model->getOriginal();

        $originalValuesChanged = [];

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