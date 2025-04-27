<?php

namespace Sepalaravel\SepaLaravel;

use Illuminate\Support\ServiceProvider;
use SepaLaravel\SepaLaravel\Facades\SepaLaravel;

class SepaLaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('sepa-laravel', function ($app) {
            return new SepaLaravel;
        });
    }

    public function boot()
    {
        // Publish configuration file if needed
        $this->publishes([
            __DIR__.'/../config/sepa-laravel.php' => config_path('sepa-laravel.php'),
        ], 'config');
    }
}
