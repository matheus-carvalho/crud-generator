<?php

/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

namespace Matheuscarvalho\Crudgenerator;

use Illuminate\Support\ServiceProvider;
use Matheuscarvalho\Crudgenerator\Commands\CrudGenerator;

/**
 * @method commands(string[] $array)
 * @method publishes(array $array)
 * @property $app
 */
class CrudGeneratorServiceProvider extends ServiceProvider {
    /** @noinspection PhpUndefinedFunctionInspection
     * @noinspection PhpUnused
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/css' => base_path('public/css'),
            __DIR__.'/config' => config_path(),
        ]);
    }

    /** @noinspection PhpUnused */
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
     * @noinspection PhpUnused
     */
    public function provides(): array
    {
        return ['command.generate:crud'];
    }
}
