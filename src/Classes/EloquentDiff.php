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
     * @param $model
     * @return array
     */
    public static function track($model)
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