# Laraval Revision Tracking Model Changes
Laraval Revision Tracking is a Laravel package that tracks the [Eloquent Model](https://laravel.com/docs/6.x/eloquent) changes, it can store, restore, retrieve the Model changes.

## Requirements
1. [Laravel 6.x](https://laravel.com/docs/6.x/releases)
2. The package can only work in [Laravel](https://laravel.com/) project.
3. The package can only work with a Model which has a primary key.

## Before start
The Laraval Revision Tracking package does work with a Model which does not have the ```int``` and ```auto_increment``` primary key, for example, a [custom key type](https://laravel.com/docs/5.8/eloquent#eloquent-model-conventions) ```string``` as the primary key, but rollback the revisions will be very tricky after the Model primary key changed. So **we suggest you to use the ```int``` and ```auto_increment``` as the primary key type and avoid changing the primary key**.

## Installation
### Install via [composer](https://getcomposer.org/doc/00-intro.md)

```
composer require luminateone/revision-tracking
```

### Add service provider
Add the service provider to the ```providers``` array in the ```config/app.php``` config file as follows:
```
'providers' => [

    ...

    LuminateOne\RevisionTracking\Providers\RevisionServiceProvider::class,
]
```

### Publish the config and migrations
Run the following command to publish the package config file, named as ```revision_tracking.php```, and the migration file for the ```revision``` table:
```bash
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider"
```

### Run migrations

#### If you are running mode ```all```, run this command:
```bash
php artisan migrate
```

#### If you are running mode ```single```, run the following command for each model you want to track:
```Please include the namespace```
```bash
php artisan table:revision {modelName}
```

## Docs

- [Basic Usage](#markdown-header-basic-usage)

#### Basic Usage

Use the ```Revisionable``` [Trait](https://www.php.net/manual/en/language.oop5.traits.php) to monitor the Model changes.
Include the ```LuminateOne\RevisionTracking\Traits``` namespace and use ```Revisionable```

```php
<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use LuminateOne\RevisionTracking\Traits\Revisionable;

class ExampleModel extends Model
{
    use Revisionable;
}
```

After a Model is updated, you can get the all the revisions like this:
```php
// Returns collection of revision
$allRevisions = $model->allRevisions()->get();
```

```allRevisions()``` will return a ```Builder```, so you can do stuff like that:
```php
// Returns collection of revision
$allRevisions = $model->allRevisions()->where('created_at', '>', 'some date')->get();
```

You can get a single revision with a ```revision id``` for a specific Model like this:
```php
// Returns a single revision
$revision = $model->getRevision($revisionId);
```

You can get rollback to a specific revision with a ```revision id``` for a specific Model like this:
```php
// This function takes two parameters: 
// integer, an id of a revision
// boolean, if set true if will save the “rollback” as a new revision of the model.
//          if set to false, it will delete the revisions that came after that revision

// This will save the "rollabck" as a new reivsion
$model->rollback($revisionId);

// This will delete all the revisions that came after that revision after rollback
$model->rollback($revisionId, false);
```

## Config file 
```php
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
    | Defines the action of Revisionable after delete a Model
    |--------------------------------------------------------------------------
    |
    | If set to true the revisions will be deleted, after the Model is deleted,
    | If set to false the revision will not be deleted, after the Model is deleted
    */

    'remove_on_delete' => env('REVISION_REMOVE_ON_DELETE', false),

    /*
    |--------------------------------------------------------------------------
    | Defines the revision mode
    |--------------------------------------------------------------------------
    |
    | all => Revisions will be stored in one table
    | single => Revisions will be stored in a separate table based on the model
    */

    'mode' => env('REVISION_MODE', 'all'),

];
```