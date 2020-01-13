# Laraval Revision Tracking Model Changes
Laraval Revision Tracking is a Laravel package that tracks the [Eloquent Model](https://laravel.com/docs/6.x/eloquent) changes, it can store, restore, retrieve the Model changes.

## Requirements
1. [Laravel 5.8 and above](https://laravel.com/docs/5.8/releases)
2. [PHP 7.1.0 and above](https://www.php.net/releases/7_1_0.php)
2. The package can only work in [Laravel](https://laravel.com/) project.
3. The package can only work with a Model which has a primary key.

## Before you start
The Laraval Revision Tracking package does work with a Model which does not have the ```int``` primary key, for example, a [custom key type](https://laravel.com/docs/5.8/eloquent#eloquent-model-conventions) ```string``` as the primary key, but rollback the revisions will be very tricky after the Model primary key changed. So **we suggest you to use the ```int``` as the primary key type and avoid changing the primary key**.

## Installation
### Install via [composer](https://getcomposer.org/doc/00-intro.md)

```
composer require luminateone/revision-tracking
```

### Publish the config and migrations
Run the following command to publish the package config file and migration file:
```bash
// Publish the config and migration file at once
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider"

// Publish the config file only
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider" --tag="config"

// Publish the migration file only
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider" --tag="migrations"
```

### Run migrations

#### If you are running mode ```all```, run this command:
Mode ```all```, revisions will be stored in one table
```bash
php artisan migrate
```

#### If you are running mode ```single```, run the following command for each model you want to track:
Mode ```single```, revisions will be stored in a separate table based on the model
```bash
// Please include the namespace
php artisan table:revision {modelName}
```
See the [revision_tracking.php](config/config.php) config file for more detail.
## Docs

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

```allRevisions()``` will return a ```EloquentBuilder```, so you still can build query. 

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