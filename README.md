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

**So we suggest you use the `int` as the primary key type and avoid changing the primary key**.

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

## Features

#### Track and rollback to the changes of a single model.
- This package can track a single model changes after the model gets created, updated, and deleted. [See example](#markdown-header-controller)
- This package can also rollback to a specific revision. [See example](#markdown-header-controller)

#### Track and rollback to the changes of a model when it has relations loaded.
- Before you go to the example, please read through the [Relation definitions](#markdown-header-relation-definitions).
- When a model has relations loaded, this package will create a relational revision. [See example](#markdown-header-create-relational-revision)
- When performing rolling back, this package will restore the revisions for all the related models. [See example](#markdown-header-retrieve-relational-revisions)

#### Track and rollback to the changes when bulk creating, updating, deleting.
- This package can track the changes when bulk creating, updating, and deleting. [See example](#markdown-header-track-bulk-actions)

## Examples
- [Model](#markdown-header-model)
- [Controller](#markdown-header-controller)
- [Relational revision](#markdown-header-relational-revision)
    - [Relation definitions](#markdown-header-relation-definitions)
    - [Create relational revision](#markdown-header-create-relational-revision)
    - [Retrieve relational revisions](#markdown-header-retrieve-relational-revisions)
- [Track bulk actions](#markdown-header-track-bulk-actions)

#### Model

Use the `Revisionable` [Trait](https://www.php.net/manual/en/language.oop5.traits.php) to monitor the model changes.
Include the `LuminateOne\RevisionTracking\Traits` namespace and `use Revisionable`.

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
#### Controller
```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExampleModel;

class ExampleController extends Controller
{
    /*
     * Create a model
     */
    public function create(Request $request) {
        //Create model, a revision will be created after the model is created
        ExampleModel::create($request->post());
        
        // Return response
    }

    /*
     * Update a model
     */
    public function update(Request $request, $id) {
        //Query the model
        $model = ExampleModel::find($id);
        
        //Update the model, a revision will be created after the model is updated
        $model->update($request->post());
        
        // Return response
    }

    /*
     * Deleted a model
     */
    public function delete($id) {
        //Query the model
        $model = ExampleModel::find($id);
        
        //Delete the model, a revision will be created after the model is deleted
        $model->delete();
        
        // Return response
    }
    
    /*
     * Get all revisions for a specific model
     */
    public function allRevisions($id) {
        //Query the model
        $model = ExampleModel::find($id);
        
        // You can get all the revisions like this, it returns collection of revision model
        $allRevisions = $model->allRevisions()->get();
        
        // Return response
    }
    
    /*
     * Roll back to a specific revision
     */
    public function rollback($id) {
        //Query the model
        $model = ExampleModel::find($id);
        
        // allRevisions() will return a EloquentBuilder, so you still can build query. 
        $revision = $model->allRevisions()->latest('id')->first();
        $revisionId = $revision->id;
        
        // You can rollback to a specific revision with a revision id for a specific model
        // $revisionId, integer, an id of a revision
        // $rollback,   boolean, true will save the “rollback” as a new revision of the model
        //                       false will delete the revisions that came after that revision
        $model->rollback($revisionId);
        
        $model->rollback($revisionId, false);
        
        // Return response
    }
}
```

#### Relational revision

**The relational revision will only work with a Model that has the relations loaded.**

There are three models, and they have relations like this:
```php
Customer:   has many Order

Order:      belongs to Customer, 
            and has many Product
            
Product:    belongs to Order
```
##### Relation definitions:

The model and revision relations depend on the way how the model is loaded.
See the following examples:
 
###### Relation 1:
```php
// When Eager loading with relations like this
$customer = Customer::where('id', 1)->with([
    'order' => function ($order) {
        $order->with('product');
    }
])->first();

// Model relations:
Customer:   is the top-level model
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
Product:    is the top-level model
Order:      is the child model of the Product                
Customer:   is the child model of the Order

// Revision relations:
ProductRevision:    is the parent revision of the OrderRevision and CustomerRevision
OrderRevision:      is the child revision of the ProductRevision
CustomerRevision:   is the child revision of the ProductRevision
```

##### Create relational revision

If you want to create the relational revision, you have to invoke `setAsRelationalRevision()` function
with the top-level model (in the following case, the top-level model is `Customer`). 

You can create relational revision like this:
```php
public function update(Request $request, $id) {
    // Eager loading with relations
    $customer = Customer::where('id', $id)->with([
        'order' => function ($order) {
            $order->with('product');
        }
    ])->first();
    
    // Call this function after the relations are loaded
    // and before update the model
    $customer->setAsRelationalRevision();
    
    // then you can call `$customer->push()`
    // or update models separately
    
    // Return response
}
```

##### Retrieve relational revisions
You can get all the relational revisions like this:
```php
public function allRelationalRevisions($id) {
    //Query the model
    $model = ExampleModel::find($id);
    
    // allRelationalRevisions() will return a EloquentBuilder, so you still can build query. 
    $relationalRevision = $model->allRelationalRevisions()->get();
    
    // Return response
}
```
`allRelationalRevisions()` will return a `EloquentBuilder`, so you still can build query. 

You can get a single relational revisions like this:
```php
public function getRelationalRevision($id, $revisionId) {
    //Query the model
    $model = ExampleModel::find($id);
    
    // Returns a single relational revision
    $relationalRevision = $model->getRelationalRevision($reivsionId);
    
    // Return response
}
```

You can rollback to a specific relational revision with a `revision id` for a specific model like this:
```php
public function rollback($id) {
    //Query the model
    $model = ExampleModel::find($id);
    
    // allRelationalRevisions() will return a EloquentBuilder, so you still can build query. 
    $relationalRevisionId = $model->allRelationalRevisions()->latest('id')->first()->id;
        
    // $revisionId, integer, an id of a revision
    // $rollback,   boolean, true will save the “rollback” as a new revision of the model
    //                       false will delete the revisions that came after that revision
    
    $model->rollback($relationalRevisionId);
    
    $model->rollback($relationalRevisionId, false);
}
```

#### Track bulk actions
With using the functions blow, the revisions will be created for every bulk insert, update, delete.

You can track bulk insert like this: 
```php
public function bulkInsert(Request $request) {
    ExampleModel::trackBulkInsert([
       ['first_name' => 'Peter', 'last_name' => 'Owen'],
       ['first_name' => 'first name', 'last_name' => 'last name']
    ]);
}
```

You can track bulk update like this:
```php
public function bulkUpdate(Request $request) {
    ExampleModel::where('first_name', '!=', '')->orWhere('last_name', '!=', '')->trackBulkUpdate(['first_name' => 'some first name']);
}
```

You can track bulk delete like this: 
```php
public function bulkDelete() {
    ExampleModel::where('first_name', '!=', '')->trackBulkDelete();
}
```