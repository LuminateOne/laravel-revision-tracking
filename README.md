# Welcome to the LuminateOne Laraval Revision Tracking Repository
Laraval Revision Tracking is a Laravel package that tracks the Eloquent Model changes, it can store, restore, retrieve the Model changes.

## Requirements
The Laraval Revision Tracking package will only work in [Laravel](https://laravel.com/) project.

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
Run the following command to create the ```revisions``` table
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