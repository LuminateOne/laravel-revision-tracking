<?php

namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;
use LuminateOne\RevisionTracking\Models\RevisionsVersion;
use LuminateOne\RevisionTracking\Models\SingleModelRevision;

trait Revisionable
{
    /**
     *  Catch the updated, deleted event
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

    public function trackChanges($action)
    {
        if (!$this->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        $revision_identifiers = [$this->getKeyName() => $this->getKey()];

        if ($action === "deleted") {
            if(config('revision_tracking.remove_on_delete', true)){
                RevisionsVersion::where([
                    'model_name' => self::class,
                    'revision_identifiers' => serialize($revision_identifiers)
                ])->delete();
            }
            return;
        }

        $originalValuesChanged = EloquentDiff::get($this);

        // Create a new revision
        RevisionsVersion::create([
            'model_name' => self::class,
            'revision_identifiers' => serialize($revision_identifiers),
            'original_values' => serialize($originalValuesChanged)
        ]);
    }
}