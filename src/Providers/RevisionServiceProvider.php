<?php
namespace LuminateOne\RevisionTracking\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * RevisionServiceProvider registers and bootstraps the revision tracking service
 *
 * @package LuminateOne\RevisionTracking\Providers
 */
class RevisionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap revision tracking services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = realpath(__DIR__ . '/../../config/config.php');
        $this->publishes([$configPath => config_path('revision_tracking.php')], 'config');

        $migrationPath = realpath(__DIR__ . '/../../migrations');
        $this->loadMigrationsFrom($migrationPath);
    }
}