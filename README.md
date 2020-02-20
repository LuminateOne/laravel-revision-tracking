# Laravel Revision Tracking
Laravel Revision Tracking is a Laravel package that tracks the [Eloquent model](https://laravel.com/docs/6.x/eloquent) 
changes. It can store, restore, retrieve all the Model changes. It stores only the diff of fields.

## Requirements
1. [Laravel 5.8 and above](https://laravel.com/docs/5.8/releases)
2. [PHP 7.1.0 and above](https://www.php.net/releases/7_1_0.php)
3. [MySQL 5.7.8 and above](https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-8.html)
4. This package can only work with models that have a primary key.

## Before you start
The Laraval Revision Tracking package does work with a model that does not have the `int` primary key, for example, 
a [custom key type](https://laravel.com/docs/5.8/eloquent#eloquent-model-conventions) `string` as the primary key, 
but rollback the revisions will be very tricky after the model primary key changed. 

**Please use the `int` as the primary key type and avoid changing the primary key**.

## Getting Started
### Install via [composer](https://getcomposer.org/doc/00-intro.md)
`cd to/your/project` and run the following command:
```
composer require luminateone/revision-tracking
```

### Publish the config and migrations
Run the following command to publish the config file and migration file, 
it will copy and paste the config and migration file from the package 
to the corresponding folder of your project:
```bash
// Publish the config and migration file
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider"
```

### Run migrations

#### If you are running mode `all`, run this command:
Mode `all`, revisions will be stored in one table, you can change the mode in `./config/revision_tracking.php`.
```bash
php artisan migrate
```

#### If you are running mode `single`, run the following command for each model you want to track:
Mode `single`, revisions will be stored in a separate table based on the model, 
you can change the mode in `./config/revision_tracking.php`.
```bash
// Please include the namespace
php artisan table:revision {modelName}
```
See the [revision_tracking.php](config/config.php) config file for more detail.

### Set up model
Put these two line into your Model class `use LuminateOne\RevisionTracking\Traits\Revisionable;` and `use Revisionable;`.

```php
<?php

use LuminateOne\RevisionTracking\Traits\Revisionable;

class ExampleModel extends Model
{
    use Revisionable;
}
```

## Features
- [Single model](#markdown-header-track-changes-of-a-single-model)
    - [Track](#markdown-header-track-changes-of-a-single-model)
    - [Retrieve all revision](#markdown-header-get-all-revisions)
    - [Retrieve single revision](#markdown-header-get-a-single-revision)
- [Relational revision](#markdown-header-track-the-changes-of-a-model-when-it-has-relations-loaded)
    - [Track](#markdown-header-track-the-changes-of-a-model-when-it-has-relations-loaded)
    - [Retrieve all relational revision](#markdown-header-retrieve-all-relational-revisions)
    - [Retrieve a single relational revision](#markdown-header-get-a-single-relational-revision)
- [Track bulk changes](#markdown-header-track-bulk-actions)
- [Rollback](#markdown-header-rollback-to-a-revision)

#### Track changes of a single model
You can track a single model changes like this:
```php
public function update(Request $request, $id) {
    $model = ExampleModel::find($id);
    
    //A revision will be created after the model is updated
    $model->update($request->post());
}

public function delete($id) {
    $model = ExampleModel::find($id); 
      
    //A revision will be created after the model is deleted
    $model->delete();
}
```

#### Get all revisions
You can retrieve all the revisions for a specific model like this:

**`allRevisions()` will return an `EloquentBuilder`, so you still can build query.**
```php
$model = ExampleModel::find($id);

// It returns collection of revision model
$allRevisions = $model->allRevisions()->get();
```

#### Get a single revision
You can get a single revisions for a specific model like this:
```php
$model = ExampleModel::find($id);

// Returns a single revision
$revision = $model->getRevision($reivsionId);
```

#### Track the changes of a model when it has relations loaded.
You can track the changes of all the related models when a model has its relations loaded like this:

If you want to create the relational revision, you have to invoke `setAsRelationalRevision()` function
with the top-level model (in the following case, the top-level model is `Customer`).

**The relational revision will only work with a Model that has the relations loaded.** 
```php
//There are two models, and they have relations like this:
Customer:   has many Order
Order:      belongs to Customer

// Eager loading with relations
$customer = Customer::where('id', $id)->with('order')->first();

// Call this function after the relations are loaded
// and before update the model
$customer->setAsRelationalRevision();

// then you can update models
```

#### Retrieve all relational revisions
`allRelationalRevisions()` will return a `EloquentBuilder`, so you still can build query.

You can retrieve all the relational revisions for a specific model like this:
```php
// allRelationalRevisions() will return a EloquentBuilder, so you still can build query.
$relationalRevision = $model->allRelationalRevisions()->get();
```
#### Get a single relational revision
You can get a single relational revisions like this:
```php
// Returns a single relational revision
$relationalRevision = $model->getRelationalRevision($reivsionId);
```

#### Track bulk actions
You can track bulk update and delete like this:
```php
// Create revisions for each update
ExampleModel::where('first_name', '!=', '')->orWhere('last_name', '!=', '')->updateTracked(['first_name' => 'some first name']);

// Create revisions for each delete
ExampleModel::where('first_name', '!=', '')->deleteTracked();
```

#### Rollback to a revision
If it is a relational revision it will rollback the related revision.

You can rollback to a specific revision for a model like this:
```php
$model = ExampleModel::find($id);

// You can rollback to a specific revision with a revision id for a specific model
// $revisionId, integer, an id of a revision
// $rollback,   boolean, true will save the “rollback” as a new revision of the model
//                       false will delete the revisions that came after that revision
$model->rollback($revisionId);

$model->rollback($revisionId, false);
```