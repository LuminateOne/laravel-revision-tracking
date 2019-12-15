<?php
namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use LuminateOne\RevisionTracking\RevisionTracking;
use LuminateOne\RevisionTracking\Models\RevisionModel;
use LuminateOne\RevisionTracking\Models\SingleRevisionModel;

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

        $originalValuesChanged = RevisionTracking::eloquentDiff($this);

        RevisionTracking::eloquentStoreDiff($this, $originalValuesChanged);
    }

    /**
     * Check the current RevisionModel Mode and get the corresponding Model
     * for the revision table
     *
     * @return RevisionModel|SingleRevisionModel
     */
    public function getRevisionModel()
    {
        $revisionTableName = null;
        if ($this->revisionMode() === 'all') {
            $revisionTableName = 'revision_versions';
        } else {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
        }

        $revisionModel = new RevisionModel();
        $revisionModel->setTable($revisionTableName);

        return $revisionModel;
    }

    public function revisionMode()
    {
        return config('revision_tracking.mode', 'all');
    }
}