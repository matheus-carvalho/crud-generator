<?php
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */

namespace Matheuscarvalho\Crudgenerator\Commands;

use Illuminate\Console\Command;
use Matheuscarvalho\Crudgenerator\Helpers\State;
use Matheuscarvalho\Crudgenerator\Helpers\Translator;
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
 * @method info(string $string)
 */
class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:crud 
                                {table : Table name (snake_case) }
                                {--resource= : The resource name (PascalCase) which will be used to name all files}
                                {--style= : Specifies the style | [default, none] | Default = default} 
                                {--language= : Specifies the language | [br, en] | Default = en}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CRUD with Model, Controller, routes, FormRequest and views based on given Migration.';

    /**
     * @var State
     */
    private $state;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        /** @noinspection PhpUndefinedClassInspection */
        parent::__construct();
        $this->state = State::getInstance();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->option('resource') ?? null;
        $migration = $this->argument('table');

        if (!$this->validateArguments($modelName, $migration)) {
            return;
        }

        $this->defineConfigs();

        if (!$this->scanMigration()) {
            $this->error('Invalid migration.');
            return;
        }

        $this->buildModel($modelName);

        $controllerName = ucfirst($modelName) . "Controller";
        $this->state->setControllerName($controllerName);

        $this->buildRoutes();
        $this->buildViews();
        $this->buildRequest($modelName);
        $this->buildController();
    }

    /**
     * Scans and validate migration
     * @return bool
     */
    private function scanMigration(): bool
    {
        $migrationWorker = new MigrationWorker();
        if (!$migrationWorker->scan()) {
            return false;
        }

        $tableName = $this->state->getTableName();
        if (!$tableName) {
            return false;
        }

        $this->call('migrate');
        return true;
    }

    /**
     * Builds the model
     * @param string $modelName
     * @return void
     */
    private function buildModel(string $modelName)
    {
        $this->call('make:model', [
            'name' => $modelName
        ]);

        $modelWorker = new ModelWorker();
        $modelWorker->build();
    }

    /**
     * Builds the views
     * @return void
     */
    private function buildViews()
    {
        $viewWorker = new ViewWorker();
        $viewWorker->build();
        $this->info('Views created successfully.');
    }

    /**
     * Builds the request
     * @param string $modelName
     * @return void
     */
    private function buildRequest(string $modelName)
    {
        $requestName = $modelName . "Request";
        $this->state->setRequestName($requestName);

        $this->call('make:request', [
            'name' => $requestName
        ]);
        $requestWorker = new RequestWorker();
        $requestWorker->build();
    }

    /**
     * Builds controller
     * @return void
     */
    private function buildController()
    {
        $this->call('make:controller', [
            'name' => $this->state->getControllerName()
        ]);

        $controllerWorker = new ControllerWorker();
        $controllerWorker->build();
    }

    /**
     * Builds routes
     * @return void
     */
    private function buildRoutes()
    {
        $routeWorker = new RouteWorker();
        $routeWorker->build();
        $this->info('Routes created successfully.');
    }

    /**
     * Defines and store the configs in state
     * @return void
     */
    private function defineConfigs()
    {
        $style = $this->defineStyle();
        $this->state->setStyle($style);

        $translator = new Translator();
        $language = $this->defineLanguage();
        $translated = $translator->getTranslated($language);
        $this->state->setTranslated($translated);

        $paginationPerPage = $this->definePaginationPerPage();
        $this->state->setPaginationPerPage($paginationPerPage);
    }

    /**
     * Validate the required arguments
     * @param string|null $modelName
     * @param string $migration
     * @return bool
     */
    private function validateArguments(?string $modelName, string $migration): bool
    {
        if (!$modelName) {
            $this->error('Not enough arguments (missing: "--resource").');
            return false;
        }

        if (!$migration) {
            $this->error('Not enough arguments (missing: "table").');
            return false;
        }

        $this->state->setMigration($migration);
        $this->state->setModelName($modelName);

        return true;
    }

    /**
     * Defines the language inside CRUD files
     * @return string
     */
    private function defineLanguage(): string
    {
        $parameterLanguage = $this->option('language');

        return [
            'br' => 'br',
            'en' => 'en'
        ][$parameterLanguage] ?? config('crudgenerator.language') ?? 'en';
    }

    private function defineStyle(): string
    {
        $parameterStyle = $this->option('style');

        return [
            'default' => 'default',
            'none' => 'none'
        ][$parameterStyle] ?? config('crudgenerator.style') ?? 'default';
    }

    private function definePaginationPerPage(): int
    {
        return config('crudgenerator.pagination_per_page') ?? 5;
    }
}
