<?php
/** @noinspection PhpUnused */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

namespace Matheuscarvalho\Crudgenerator\Commands;

use Illuminate\Console\Command;
use Matheuscarvalho\Crudgenerator\Workers\ControllerWorker;
use Matheuscarvalho\Crudgenerator\Workers\MigrationWorker;
use Matheuscarvalho\Crudgenerator\Workers\ModelWorker;
use Matheuscarvalho\Crudgenerator\Workers\RequestWorker;
use Matheuscarvalho\Crudgenerator\Workers\RouteWorker;
use Matheuscarvalho\Crudgenerator\Workers\ViewWorker;

/**
 * @method argument(string $string)
 * @method option(string $string)
 * @method error(string $string)
 * @method call(string $string, array $params = [])
 */
class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:crud 
                                {--model-name= : Name of the Model}
                                {--without-style : Disable the default style} 
                                {--language= : Specifies the language of files generated | br, en | Default = en}
                                {migration : Full Name of the Migration to be used as base}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CRUD with Model, Controller, Routes and Views based on given Migration.';

    /**
     * @var MigrationWorker
     */
    private $migrationWorker;

    /**
     * @var ModelWorker
     */
    private $modelWorker;

    /**
     * @var ViewWorker
     */
    private $viewWorker;

    /**
     * @var ControllerWorker
     */
    private $controllerWorker;

    /**
     * @var RouteWorker
     */
    private $routeWorker;

    /**
     * @var RequestWorker
     */
    private $requestWorker;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::__construct();
        $this->migrationWorker = new MigrationWorker();
        $this->modelWorker = new ModelWorker();
        $this->viewWorker = new ViewWorker();
        $this->controllerWorker = new ControllerWorker();
        $this->routeWorker = new RouteWorker();
        $this->requestWorker = new RequestWorker();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migration = $this->argument('migration');
        $modelName = $this->option('model-name') ?? null;
        $isWithoutStyle = $this->option('without-style');

        if (!$modelName) {
            $this->error('Not enough arguments (missing: "--model-name").');
        }

        $language = $this->defineLanguage();

        [$fieldList, $tableName] = $this->migrationWorker->scan($migration);

        if (!$tableName) {
            $this->error('Invalid migration (missing: table name).');
        }

        $this->call('migrate');
        $this->call('make:model', [
            'name' => $modelName
        ]);
        $this->modelWorker->build($modelName, $tableName, $fieldList);
        $viewFolder = $this->viewWorker->build($modelName, $language, $fieldList, $isWithoutStyle);

        $requestName = $modelName . "Request";
        $this->call('make:request', [
            'name' => $requestName
        ]);
        $this->requestWorker->build($requestName, $modelName, $fieldList, $language);

        $controllerName = ucfirst($modelName) . "Controller";
        $this->call('make:controller', [
            'name' => $controllerName
        ]);

        $this->controllerWorker->build($controllerName, $modelName, $fieldList, $viewFolder, $language);
        $this->routeWorker->build($controllerName, $viewFolder, $modelName);
    }

    /**
     * Defines the language inside CRUD files
     * @return string
     */
    public function defineLanguage(): string
    {
        $parameterLanguage = $this->option('language');

        /** @noinspection PhpUndefinedFunctionInspection */
        return [
            'br' => 'br',
            'en' => 'en'
        ][$parameterLanguage] ?? config('crudgenerator.language') ?? 'en';
    }
}
