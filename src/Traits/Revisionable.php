<?php

namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;
use LuminateOne\RevisionTracking\Classes\EloquentStoreRevision;
use LuminateOne\RevisionTracking\Models\RevisionsVersion;

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
        if (!$this->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        $originalValuesChanged = EloquentDiff::get($this);

        EloquentStoreRevision::save($this, $originalValuesChanged);
    }
}