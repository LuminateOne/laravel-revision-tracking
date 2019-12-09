<?php
namespace LuminateOne\RevisionTracking\Traits;

use LuminateOne\RevisionTracking\Classes\Initialise;
use LuminateOne\RevisionTracking\Classes\EloquentDiff;
use Log;

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
        Log::info('action: ' . $action);

        // Initialise the Model,
        // get the revision table name,
        // get the primary key or unique key of the record whit values
        $initialiseResult = Initialise::ini($this);

        Log::info('Initialised');

        Log::info($this->revision_table);

        Log::info($this->revision_identifiers);

        if($initialiseResult === true){

            Log::info('checkModel: succeed');

            $originalValuesChanged = EloquentDiff::track($this);

            Log::info(print_r($originalValuesChanged, true));

        }
    }
}