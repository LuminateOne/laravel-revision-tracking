<?php

namespace LuminateOne\RevisionTracking\Traits;

use Log;
use DB;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;

trait Revisionable
{

    /**
     *  Catch the created, updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::updated(function ($model) {
            $model->trackChanges('updated');
        });

        static::deleted(function ($model) {
            $model->trackChanges('deleted');
        });
    }


    /**
     * @param $action
     */
    public function trackChanges($action)
    {
        $revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();

        if (!$this->getKeyName()) {
            Log::warning(print_r($this->getTable() . " does not have primary keys.", true));
            return;
        }

        $revision_identifiers = [$this->getKeyName() => $this->getKey()];

        $originalValuesChanged = EloquentDiff::get($this);
    }
}