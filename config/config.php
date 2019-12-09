<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 9/12/2019
 * Time: 12:13 PM
 */

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
    'revision_table_prefix' => env('REVISION_TABLE_PREFIX', 'revisions_'),

    /*
    |--------------------------------------------------------------------------
    | Defines the action of revisionable after delete a Model
    |--------------------------------------------------------------------------
    |
    | If set to true the revisions will be deleted, after the Model is deleted,
    | If set to false the revision will not be deleted, after the Model is deleted
    */

    'delete_revisions_on_deletion' => true,

];