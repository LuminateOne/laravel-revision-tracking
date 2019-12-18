# Welcome to the LuminateOne Laraval Revision Tracking Repository
Laraval Revision Tracking is a Laravel package that tracks the Eloquent Model changes, it can store, restore, retrieve the Model changes.

## Requirements
1. The Laraval Revision Tracking package can only work in [Laravel](https://laravel.com/) project.
2. The Laraval Revision Tracking package can only work with a Model which has a primary key.

## Before start
The Laraval Revision Tracking package does work with a Model which does not have the ```int``` and ```auto_increment``` primary key, for example, a ```string``` as the primary key, but rollback the revisions will be very tricky after the Model primary key changed. So **we suggest you to avoid changing the primary key or use the ```int``` and ```auto_increment``` as the primary key type**.

## Setup
### 1. Install via composer

Add ```require``` and ```repositories``` to ```composer.json```.
```
"require": {
    
    ...
    
    "luminateone/revision-tracking": "dev-staging"
}

"repositories": [

    ...
    
    {
        "type": "vcs",
        "url":  "https://luminateone@bitbucket.org/luminateone/laravel-revision-tracking.git"
    }
]
```

Run the following command to pull the ```staging``` branch, it may ask you to enter your ```bitbucket``` user name and password
```
composer require luminateone/revision-tracking
```

### 2. Add service provider
Add the service provider to the ```providers``` array in the ```config/app.php``` config file as follows:
```
'providers' => [

    ...

    LuminateOne\RevisionTracking\Providers\RevisionServiceProvider::class,
]
```

### 3. Publish the config
Run the following command to publish the package config file, named as ```revision_tracking.php```:
```
php artisan vendor:publish --provider="LuminateOne\RevisionTracking\Providers\RevisionServiceProvider"
```

### 4. Run migrations
Run the following command to create the ```revisions``` table, this table will be used when the revision mode is set to ```all```.
```
php artisan migrate
```

### 5. Command to create the revision table when the revision mode is set to ```single```
Run the following command to create the revisions table for a single Model.
```Please include the namespace```
```
php artisan table:revision {modelName}
```

## Config file ```revision_tracking.php```
There are three options:
##### 1. ```mode```: default is ```all```
    all => Revisions will be stored in one table
    single => Revisions will be stored in a separate table based on the model
    
##### 2. ```table_prefix ```: default is ```revisions_```
It defines the table prefix when the revision mode is set the ```single```

##### 3. ```remove_on_delete```: default is ```false```
If set to true, when a Model is deleted the revisions of that Model will be deleted too.


## Code example

### Notice: If you set the revision mode to ```single```, do not forget to run ```php artisan table:revision {modelName}```
#### 1. Models
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

#### 2. Controllers

```php
<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class ExampleModelController extends Controller
{

   /**
     * Get all revisions for a specific Model.
     *
     * @param  integer Model primary key
     * 
     * @return \Illuminate\Http\JsonResponse
     */
   public function getAllRevision($id){
       $exmapleMode = ExampleModel::find($id);
        
       $allRevisions = $exmapleMode->allRevisions()->get();
        
       return response()->json(['allRevisions' => $allRevisions]);
   }
    
   /**
     * Get a single revision for a specific Model.
     * 
     * @param  Request $request
     * @param  integer Model primary key
     * 
     * @return \Illuminate\Http\JsonResponse
     */
   public function getRevision(Request $request, $id){
       $revisionId = $request->revisionId;
        
       $exmapleMode = ExampleModel::find($id);
        
       $revision = $exmapleMode->getRevision($revisionId);
       
       return response()->json(['revision' => $revision]);
   }
   
    /**
      * Rollback to a specific revision for a specific Model.
      *
      * @param  Request $request
      * @param  integer Model primary key
      *  
      * @return \Illuminate\Http\JsonResponse
      */
    public function rollback(Request $request, $id){
        $revisionId = $request->revisionId;
        
        $exmapleMode = ExampleModel::find($id);
        
        $exmapleMode->rollback($revisionId);
            
        $restoredModel = ExampleModel::find($id);
        
        return response()->json(['oldModel' => $exmapleMode, 'restoredModel' => $restoredModel]);
    }
    
    /**
      * Rollback to a specific revision for a specific Model 
      * and delete the revisions that came after the restored revision.
      *
      * @param  Request $request
      * @param  integer Model primary key
      * 
      * @return \Illuminate\Http\JsonResponse
      */
    public function rollbackAndDeleteRevision(Request $request, $id){
        $revisionId = $request->revisionId;
        
        $exmapleMode = ExampleModel::find($id);
        
        // The rollback function takes two parameters
        // integer $revisionId      Revision ID for the Model
        // boolean $saveAsRevision. Default is true
        //      true =>  save the “rollback” as a new revision of the model
        //      false => rollback to a specific revision and delete all the revisions that came after that revision
        $exmapleMode->rollback($revisionId, false);
            
        $restoredModel = ExampleModel::find($id);
        
        // Now the revision cound shoule be 0
        $revisionCount = $exmapleMode->allRevisions()->count();
        
        return response()->json([
          'oldModel' => $exmapleMode, 
          'restoredModel' => $restoredModel,
          'revisionCount' => $revisionCount
        ]);
    }
}
```

