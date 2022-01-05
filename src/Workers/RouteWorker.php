<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

class RouteWorker
{
    public function build(string $controllerName, string $resourceName, string $modelName)
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $routePath = base_path('routes');
        $routesFile = $routePath."/web.php";
        $offset = 2;

        $importController  = "use App\Http\Controllers\\$controllerName;";

        $allFileLines = file($routesFile, FILE_IGNORE_NEW_LINES);
        array_splice($allFileLines, $offset, 0, $importController);
        file_put_contents($routesFile, join("\n", $allFileLines));

        $insert  = "\n\nRoute::get('/$resourceName', [$controllerName::class, 'index'])->name('index$modelName');";
        $insert .= "\nRoute::get('/$resourceName/create', [$controllerName::class, 'create'])->name('create$modelName');";
        $insert .= "\nRoute::get('/$resourceName/edit/{id}', [$controllerName::class, 'edit'])->name('edit$modelName');";
        $insert .= "\nRoute::post('/$resourceName/store', [$controllerName::class, 'store'])->name('store$modelName');";
        $insert .= "\nRoute::put('/$resourceName/update/{id}', [$controllerName::class, 'update'])->name('update$modelName');";
        $insert .= "\nRoute::delete('/$resourceName/delete/{id}', [$controllerName::class, 'destroy'])->name('delete$modelName');";

        file_put_contents($routesFile, $insert, FILE_APPEND);
    }
}
