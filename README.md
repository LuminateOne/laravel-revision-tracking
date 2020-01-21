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
- [Basic Usage](#markdown-header-basic-usage)
- [Relational revision](#markdown-header-relational-revision)
    - [Relation definitions](#markdown-header-relation-definitions)
    - [Create relational revision automatically](#markdown-header-create-relational-revision-automatically)
    - [Create relational revision manually](#markdown-header-create-relational-revision-manually)
    - [Update models and create relational revision manually](#markdown-header-updated-the-model-and-create-relational-revision-manually)
    - [Retrieve relational revisions](#markdown-header-retrieve-relational-revisions)

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

You can rollback to a specific revision with a `revision id` for a specific model like this:
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
    Customer:   has many Order
    
    Order:      belonges to Customer, 
                and has many Product
                
    Product:    belonges to Order
```
##### Relation definitions:
###### Relation 1:
```php
    // When Eager loading with relations like this
    $customer = Customer::where('id', 1)->with([
        'order' => function ($order) {
            $order->with('product');
        }
    ])->first();
    
    // Model relations:
    Customer:   is the parent model of the Order and Product
    Order:      is the child model of the Customer
    Product:    is the child model of the Customer
     
    // Revision relations:
    CustomerRevision:    is the parent revision of the OrderRevision and ProductRevision
    OrderRevision:       is the child revision of the CustomerRevision                         
    ProductRevision:     is the child revision of the CustomerRevision
```

###### Relation 2:
```php
    // When Eager loading with relations like this
    $product = Product::where('id', 1)->with([
        'order' => function ($order) {
            $order->with('customer');
        }
    ])->first();
    
    // Model relations:
    Product:    is the parent model of the Order and Customer
    Order:      is the child model of the Product                
    Customer:   is the child model of the Order
    
    // Revision relations:
    ProductRevision:    is the parent revision of the OrderRevision and CustomerRevision
    OrderRevision:      is the child revision of the ProductRevision
    CustomerRevision:   is the child revision of the ProductRevision
 ```

##### Create relational revision automatically

If you want to create the relational revision automatically, 
the most top parent model has to updated, in this case the 
most top parent model is `$customer`. Otherwise you can 
[create the relational revision manually](#markdown-create-relational-revision-manually).

You can create relational revision automatically like this:
```php
    // Eager loading with relations
    $customer = Customer::where('id', 1)->with([
        'order' => function ($order) {
            $order->with('product');
        }
    ])->first();
    
    // Your logic here
    // Assign new values to the model
    
    // Call $model->push() to update the model and its related models
    $customer->push();
```

##### Create relational revision manually

If most top model will not be updated, you need to call this method manually 
`before you update the model`, after the `child` model is updated it will 
create a revision for its parent.

```php
// Eager loading with relations
$customer = Customer::where('id', 1)->with([
    'order' => function ($order) {
        $order->with('product');
    }
])->first();

$customer->setAsRelationalRevision();

// Your logic here

// After the child model (order or product) is updated it will create 
// a revision for the customer, and setup the relation between 
// customer revision and the order reivsion
$customer->push();
```

##### Update models and create relational revision manually
You can updated the model manually like this:

When update the model manually, you have to update models from 
most top model to the most bottom model, in the following case
from `$customer` to `$product`.

```php
// Eager loading with relations
$customer = Customer::where('id', 1)->with([
    'order' => function ($order) {
        $order->with('product');
    }
])->first();

// call `setAsRelationalRevision` to set relations with its child model manually
$customer->setAsRelationalRevision();

// Your logic here
// update $customer, $order and $product

// This one is equvilent to the $customer->push()
$customer->save();
foreach($customer->order as $aOrder){
    $aOrder->save();
    foreach($aOrder->$product as $aProduct){
        $aProduct->save();
    }
}
```

##### Retrieve relational revisions
You can get the all relational revisions like this:
```php
// Returns collection of relational revision
$relationalRevision = $model->allRelationalRevisions();
```
`allRevisions()` will return a `EloquentBuilder`, so you still can build query. 

You can get a single relational revisions like this:
```php
// Returns a single relational revision
$relationalRevision = $model->getRelationalRevision($reivsionId);
```

You can check if a revision is a relational revisions like this:
```php
// Returns boolean
if($reivsion->hasRelatedRevision()){
    // Your logic here
}
```

You can rollback to a specific relational revision with a `revision id` for a specific model like this:
```php
// $revisionId, integer, an id of a revision
// $rollback,   boolean, true will save the “rollback” as a new revision of the model
//                       false will delete the revisions that came after that revision

$model->rollback($relationalRevisionId);

$model->rollback($relationalRevisionId, false);
```
