<?php

namespace LuminateOne\RevisionTracking\Traits;

use ErrorException;
use LuminateOne\RevisionTracking\Classes\EloquentDeleted;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;
use LuminateOne\RevisionTracking\Classes\EloquentStoreRevision;
use LuminateOne\RevisionTracking\Models\RevisionsVersion;
use LuminateOne\RevisionTracking\Models\SingleRevisionModel;

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

        if ($action === "deleted") {
            EloquentDeleted::handle($this);
            return;
        }

        $originalValuesChanged = EloquentDiff::get($this);

        EloquentStoreRevision::save($this, $originalValuesChanged);
    }


    /**
     * Check the Revision Mode
     * If mode = 0, return RevisionsVersion
     * If mode != 0, return SingleRevisionModel, and set the corresponding table to the Model
     * @return RevisionsVersion|SingleRevisionModel
     */
    public function getRevisionModel(){

        if(config('revision_tracking.mode', 0) === 0){
            return new RevisionsVersion();
        }else {
            $revisionTableName = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();

            $singleRevisionModel = new SingleRevisionModel();
            $singleRevisionModel->setTable($revisionTableName);
            $singleRevisionModel->createTableIfNotExist();

           return $singleRevisionModel;
        }
    }
}