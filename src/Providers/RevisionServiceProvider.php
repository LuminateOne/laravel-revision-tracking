<?php
namespace LuminateOne\RevisionTracking\Providers;

use Illuminate\Support\ServiceProvider;
use LuminateOne\RevisionTracking\Commands\CreateModelRevisionTable;

/**
 * RevisionServiceProvider registers and bootstraps the revision tracking service
 *
 * @package LuminateOne\RevisionTracking\Providers
 */
class RevisionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'revision_tracking');
    }

    /**
     * Bootstrap revision tracking services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $configPath = realpath(__DIR__ . '/../../config/config.php');
            $this->publishes([$configPath => config_path('revision_tracking.php')], 'config');

            $this->publishes([
                __DIR__ . '/../../migrations/create_revisions_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_posts_table.php'),
            ], 'migrations');

            $this->commands([
                CreateModelRevisionTable::class
            ]);
        }
    }
}