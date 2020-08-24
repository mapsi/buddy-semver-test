<?php

namespace App\Providers;

use App\Classes\Drupal;
use Illuminate\Support\ServiceProvider;

class DrupalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Drupal::class, function ($app) {
            return new Drupal(config('globemedia.drupal'));
        });
    }
}
