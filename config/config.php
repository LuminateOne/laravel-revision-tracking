<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Revisionable config file
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Defines the revision table prefix
    |--------------------------------------------------------------------------
    |
    */
    'table_prefix' => env('REVISION_TABLE_PREFIX', 'revisions_'),

    /*
    |--------------------------------------------------------------------------
    | Defines the action of revisionable after delete a model
    |--------------------------------------------------------------------------
    |
    | If set to true the revisions will be deleted, after the model is deleted,
    | If set to false the revision will not be deleted, after the model is deleted
    */

    'remove_on_delete' => env('REVISION_REMOVE_ON_DELETE', false),

    /*
    |--------------------------------------------------------------------------
    | Defines the Revision Mode
    |--------------------------------------------------------------------------
    |
    | all => Revisions will be stored in one table
    | single => Revisions will be stored in a separate table based on the model
    */

    'mode' => env('REVISION_MODE', 'all'),

];