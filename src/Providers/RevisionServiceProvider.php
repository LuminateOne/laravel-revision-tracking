<?php
namespace LuminateOne\RevisionTracking\Providers;

use Illuminate\Support\ServiceProvider;
use LuminateOne\RevisionTracking\Commands\CreateModelRevisionTable;

class RevisionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $configPath = realpath(__DIR__ . '/../../config/config.php');
        $this->publishes([$configPath => config_path('revision_tracking.php')], 'config');

        $migrationPath = realpath(__DIR__ . '/../../migrations');
        $this->loadMigrationsFrom($migrationPath);
    }
}