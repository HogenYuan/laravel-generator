<?php

namespace App\Admin\Console\Commands\Generator;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application constant.
     *
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     */
    public function register()
    {
        $this->app->singleton('command.laravel.make.generator', function ($app) {
            return new MakeResource($app['files']);
        });
        $this->commands([
            'command.laravel.make.generator',
        ]);
    }
}
