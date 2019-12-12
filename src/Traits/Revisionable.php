<?php
namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use LuminateOne\RevisionTracking\RevisionTracking;
use LuminateOne\RevisionTracking\Models\RevisionVersion;
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
     * Check the current Revision Mode and get the corresponding Model
     * for the revision table
     *
     * @return RevisionVersion|SingleRevisionModel
     */
    public function getRevisionModel()
    {
        if ($this->revisionMode() === 0) {
            return new RevisionVersion();
        } else {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();

            $singleRevisionModel = new SingleRevisionModel();
            $singleRevisionModel->setTable($revisionTableName);

            return $singleRevisionModel;
        }
    }

    public function revisionMode()
    {
        return config('revision_tracking.mode', 0);
    }
}