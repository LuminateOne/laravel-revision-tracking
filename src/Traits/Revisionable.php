<?php
namespace LuminateOne\RevisionTracking\Traits;

use Log;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;

trait Revisionable
{

    /**
     *  Catch the updated, deleted event
     */
    public static function bootRevisionable()
    {
        static::updated(function ($model) {
            $model->trackChanges();
        });

        static::deleted(function ($model) {
            $model->trackChanges();
        });
    }

    public function trackChanges()
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