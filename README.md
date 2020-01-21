# Laraval Revision Tracking
Laraval Revision Tracking is a Laravel package that tracks the [Eloquent model](https://laravel.com/docs/6.x/eloquent) 
changes. It can store, restore, retrieve all the Model changes. It stores only the diff of fields.

## Requirements
1. [Laravel 5.8 and above](https://laravel.com/docs/5.8/releases)
2. [PHP 7.1.0 and above](https://www.php.net/releases/7_1_0.php)
3. The package can only work with models that have a primary key.

## Before you start
The Laraval Revision Tracking package does work with a model that does not have the `int` primary key, for example, 
a [custom key type](https://laravel.com/docs/5.8/eloquent#eloquent-model-conventions) `string` as the primary key, 
but rollback the revisions will be very tricky after the model primary key changed. 

**So we suggest you use the `int` as the primary key type and avoid changing the primary key**.

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

#### If you are running mode `all`, run this command:
Mode `all`, revisions will be stored in one table
```bash
php artisan migrate
```

#### If you are running mode `single`, run the following command for each model you want to track:
Mode `single`, revisions will be stored in a separate table based on the model
```bash
// Please include the namespace
php artisan table:revision {modelName}
```
See the [revision_tracking.php](config/config.php) config file for more detail.
## Docs
- [Basic Usage](#markdown-basic-usage)
- [Relational revision](#markdown-relational-revision)

#### Basic Usage

Use the `Revisionable` [Trait](https://www.php.net/manual/en/language.oop5.traits.php) to monitor the model changes.
Include the `LuminateOne\RevisionTracking\Traits` namespace and use `Revisionable`

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

After a model is updated, you can get the all the revisions like this:
```php
// Returns collection of revision
$allRevisions = $model->allRevisions()->get();
```

`allRevisions()` will return a `EloquentBuilder`, so you still can build query. 

You can get a single revision with a `revision id` for a specific model like this:
```php
// Returns a single revision
$revision = $model->getRevision($revisionId);
```

You can get rollback to a specific revision with a `revision id` for a specific model like this:
```php
// $revisionId, integer, an id of a revision
// $rollback,   boolean, true will save the “rollback” as a new revision of the model
//                       false will delete the revisions that came after that revision

$model->rollback($revisionId);

$model->rollback($revisionId, false);
```

#### Relational revision

The relational revision will only work with a Model which have the relations loaded.

There are three models, and they have relations like this:
```php
    GrandParent has many Parent
    Parent has many Child
```

You can create relational revision like this:
```php
    // Eager loading with relations
    $grandParent = GrandParent::where('id', 1)->with([
        'parent' => function ($parent) {
            $parent->with('children');
        }
    ])->first();
    
    // You logic here
    // Assign new values to the model
    
    // Call $model->push() to update the model and its related models
    $grandParent->push();
```

If the `GrandParent` will not be updated, you need to call this method manually `before you update the model`, after the `Parent` and `Child` model is updated it will create a relational revision:
```
$grandParent->setAsRelationalRevision();
``` 
