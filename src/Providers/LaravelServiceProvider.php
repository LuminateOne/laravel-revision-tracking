<?php
/**
 * Created by PhpStorm.
 * User: yiming
 * Date: 9/12/2019
 * Time: 12:15 PM
 */

namespace LuminateOne\Revisionable\Providers;

use Illuminate\Support\ServiceProvider;


class LaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/config.php');

        $this->publishes([$path => config_path('revisionable.php')], 'config');
    }
}