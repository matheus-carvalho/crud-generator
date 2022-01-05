<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

class RouteWorker
{
    /**
     * @var string
     */
    private $controllerName;

    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var string
     */
    private $modelName;

    /**
     * Builds the route file
     * @param string $controllerName
     * @param string $resourceName
     * @param string $modelName
     * @return void
     */
    public function build(string $controllerName, string $resourceName, string $modelName)
    {
        $this->controllerName = $controllerName;
        $this->resourceName = $resourceName;
        $this->modelName = $modelName;

        $routesFile = $this->getRoutesFile();
        $this->importController($routesFile);
        $this->appendRoutes($routesFile);
    }

    /**
     * Returns the full path of routes file
     * @return string
     */
    private function getRoutesFile(): string
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $routePath = base_path('routes');
        return $routePath."/web.php";
    }

    /**
     * Add the import controller part to the file
     * @param string $routesFile
     * @return void
     */
    private function importController(string $routesFile): void
    {
        $lineToImport = 2;
        $importStatement = "use App\Http\Controllers\\$this->controllerName;";
        $SPLICE_LENGTH = 0;
        $LINE_SEPARATOR = "\n";

        $allFileLines = file($routesFile, FILE_IGNORE_NEW_LINES);
        array_splice($allFileLines, $lineToImport, $SPLICE_LENGTH, $importStatement);
        file_put_contents($routesFile, join($LINE_SEPARATOR, $allFileLines));
    }

    private function appendRoutes(string $routesFile)
    {
        $appendContent = "\n";
        $appendContent .= $this->buildRoute('get', 'index');
        $appendContent .= $this->buildRoute('get', 'create');
        $appendContent .= $this->buildRoute('get', 'edit', true);
        $appendContent .= $this->buildRoute('post', 'store');
        $appendContent .= $this->buildRoute('put', 'update', true);
        $appendContent .= $this->buildRoute('delete', 'destroy', true);

        file_put_contents($routesFile, $appendContent, FILE_APPEND);
    }

    /**
     * Builds each route to append to file
     * @param string $verb
     * @param string $method
     * @param bool $hasId
     * @return string
     */
    private function buildRoute(string $verb, string $method, bool $hasId = false): string
    {
        $routeName = $method . $this->modelName;
        $routePath = "/$this->resourceName/$method";

        if ($method === "index") {
            $routePath = "/$this->resourceName";
        }
        if ($hasId) {
            $routePath = "/$this->resourceName/$method/{id}";
        }

        return "\nRoute::$verb('$routePath', [$this->controllerName::class, '$method'])->name('$routeName');";
    }
}
