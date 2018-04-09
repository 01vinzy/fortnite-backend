<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use \App\Api\Api;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
      $this->app->singleton('\App\Api\Api', function () {
        return new Api(config('api'));
      });

      $this->app->bind('api', '\App\Api\Api');
    }
}
