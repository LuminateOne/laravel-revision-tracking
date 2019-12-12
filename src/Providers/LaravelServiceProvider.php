<?php
namespace LuminateOne\RevisionTracking\Providers;

use Illuminate\Support\ServiceProvider;
use LuminateOne\RevisionTracking\Commands\AddRevisionModel;
use LuminateOne\RevisionTracking\Commands\RemoveRevisionModel;

class LaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $configPath = realpath(__DIR__ . '/../../config/config.php');
        $this->publishes([$configPath => config_path('revision_tracking.php')], 'config');

        $migrationPath = realpath(__DIR__ . '/../../migrations');
        $this->loadMigrationsFrom($migrationPath);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AddRevisionModel::class,
                RemoveRevisionModel::class,
            ]);
        }
    }
}