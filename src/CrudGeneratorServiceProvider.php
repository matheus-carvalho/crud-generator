<?php

namespace Matheuscarvalho\Crudgenerator;

use Illuminate\Support\ServiceProvider;
use Matheuscarvalho\Crudgenerator\Commands\CrudGenerator;

class CrudGeneratorServiceProvider extends ServiceProvider {
    public function boot()
    {
        $this->publishes([
            __DIR__.'/css' => base_path('public/css'),
            __DIR__.'/config' => config_path(),
        ]);
    }

    public function register()
    {
        $this->app->singleton('command.generate:crud', function () {
            return new CrudGenerator;
        });

        $this->commands(['command.generate:crud']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.generate:crud'];
    }
}
