<?php

namespace Hogen\Generator;

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
        $this->app->singleton('command.generator.make', function ($app) {
            return new MakeResource($app['files']);
        });
        $this->commands([
            'command.generator.make',
        ]);
    }
}
