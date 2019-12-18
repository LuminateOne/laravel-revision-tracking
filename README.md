# Laraval Revision Tracking

## Setup
### 1. Install via composer

Add ```require``` and ```repositories``` to ```composer.json```.
```$json
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
```$json
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

## Config file ```revision_tracking.php```
There are three options:
#### ```mode```: 
##### ```table_prefix ```: It defines the table prefix, 