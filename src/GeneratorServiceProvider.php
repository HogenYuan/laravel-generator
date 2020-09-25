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
        //发布资源
        $this->publishes([
            __DIR__ . '/publish/Generator/' => app_path('Console\\Commands\\Generator\\')
        ], 'generator');
    }

    /**
     * Register the service provider.
     *
     */
    public function register()
    {
    }
}
