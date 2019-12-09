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
        $this->createRevisionTableName();

        Log::info('Initialised');

        Log::info($this->revision_table);

        Log::info($this->revision_identifiers);

        if($initialiseResult === true){

            Log::info('checkModel: succeed');

            $originalValuesChanged = EloquentDiff::get($this);

            Log::info(print_r($originalValuesChanged, true));

        }
    }

    /**
     * Create the revision table name, with the prefix declared in the config file
     */
    private function createRevisionTableName(){
        $this->revision_table = config('revision_tracking.table_prefix', 'revisions_') . $this->getTable();
    }
}