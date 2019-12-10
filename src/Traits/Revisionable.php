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
            $model->trackChanges();
        });

        static::deleted(function ($model) {
            $model->trackChanges();
        });
    }

    public function trackChanges()
    {
        if(in_array("is_restoring", $this->appends)) {
            \Log::info("Restoring revision returned");
            return;
        }
        if (!$this->getKeyName()) {
            throw new ErrorException("the revisionable trait can only be used on models which has a primary key. The " .
                self::class . " model does not have a primary key.");
        }

        $revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();

        $singleRevisionModel = new SingleModelRevision();
        $singleRevisionModel->setTable($revision_table);
        $singleRevisionModel->createTableIfNotExists();

        $revision_identifiers = [$this->getKeyName() => $this->getKey()];

        $originalValuesChanged = EloquentDiff::get($this);

        // Create a new revision version
        $newRevisionVersion = RevisionsVersion::create(['model_name' => self::class]);

        // Store the Model changes
        $singleRevisionModel->revisions_version_id = $newRevisionVersion->id;
        $singleRevisionModel->revision_identifiers = serialize($revision_identifiers);
        $singleRevisionModel->original_values = serialize($originalValuesChanged);
        $singleRevisionModel->save();
    }
}