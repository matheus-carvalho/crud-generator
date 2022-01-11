<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\Translator;
use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class ControllerWorker
{
    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $translated;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var string
     */
    private $viewFolder;

    /**
     * @var string
     */
    private $controllerName;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
        $this->translator = new Translator();
    }

    /**
     * Builds the Controller file
     * @param string $controllerName
     * @param string $modelName
     * @param array $fieldList
     * @param string $viewFolder
     * @param string $lang
     * @return void
     */
    public function build(string $controllerName, string $modelName, array $fieldList, string $viewFolder, string $lang)
    {
        $this->modelName = $modelName;
        $this->viewFolder = $viewFolder;
        $this->controllerName = $controllerName;
        $this->translated = $this->translator->getTranslated($lang);

        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Http/Controllers/') . $controllerName . ".php";

        $requestName = $this->modelName . "Request";

        [
            $content,
            $foreignKeys,
            $fkContents,
            $fkVarNames
        ] = $this->appendHeaders($fieldList, $requestName);
        $content .= $this->appendIndex();
        $content .= $this->appendCreate($foreignKeys, $fkContents, $fkVarNames);
        $content .= $this->appendEdit($foreignKeys, $fkContents, $fkVarNames);
        $content .= $this->appendStore($requestName);
        $content .= $this->appendUpdate($requestName);
        $content .= $this->appendDelete();

        $content .= "\n}";
        file_put_contents($filePath, $content);
    }

    /**
     * Add the headers to the content and prepare the foreign keys
     * @param array $fieldList
     * @param string $requestName
     * @return array
     */
    private function appendHeaders(array $fieldList, string $requestName): array
    {

        $content = "<?php";
        $content .= "\n\nnamespace App\Http\Controllers;";
        $content .= "\n\nuse App\Http\Requests\\$requestName;";
        $content .= "\nuse Illuminate\Http\RedirectResponse;";
        $content .= "\nuse Illuminate\View\View;";
        $content .= "\nuse App\Models\\$this->modelName;";

        $foreignKeys = $this->utilsHelper->checkForeignKeys($fieldList);
        $fkContents = '';
        $fkVarNames = '';
        if ($foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $content .= "\nuse App\Models\\$fk;";

                $fkVarName = lcfirst($fk) . 'List';
                $fkVarNames .= "'$fkVarName'" . ", ";
                $fkContents .= "\n\t\t\$$fkVarName = $fk::all();";
            }
        }

        $content .= "\n\nclass $this->controllerName extends Controller\n{\n";

        return [
            $content,
            $foreignKeys,
            $fkContents,
            $fkVarNames
        ];
    }

    /**
     * Add the method index to the content
     * @return string
     */
    private function appendIndex(): string
    {
        $content = "\tpublic function index(): View";
        $content .= "\n\t{";
        $content .= "\n\t\t\$items = $this->modelName::all();";
        $content .= "\n\t\treturn view('$this->viewFolder.index', compact('items'));";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Add the method create to the content
     * @param array $foreignKeys
     * @param string $fkContents
     * @param string $fkVarNames
     * @return string
     */
    private function appendCreate(array $foreignKeys, string $fkContents, string $fkVarNames): string
    {
        $content = "\n\n\tpublic function create(): View";
        $content .= "\n\t{";
        if ($foreignKeys) {
            $content .= $fkContents;
        }
        $content .= "\n\t\treturn view('$this->viewFolder.create'";
        if ($foreignKeys) {
            $content .= ", compact(";
            $content .= rtrim($fkVarNames, ", ");
            $content .= ")";
        }
        $content .= ");";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Add the method edit to the content
     * @param array $foreignKeys
     * @param string $fkContents
     * @param string $fkVarNames
     * @return string
     */
    private function appendEdit(array $foreignKeys, string $fkContents, string $fkVarNames): string
    {
        $content  = "\n\n\tpublic function edit(\$id): View";
        $content .= "\n\t{";
        $content .= "\n\t\t\$item = $this->modelName::find(\$id);";
        if ($foreignKeys) {
            $content .= $fkContents;
        }
        $content .= "\n\t\treturn view('$this->viewFolder.create', compact(";
        if ($foreignKeys) {
            $content .= $fkVarNames;
        }
        $content .= "'item'));";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Add the method store to the content
     * @param string $requestName
     * @return string
     */
    private function appendStore(string $requestName): string
    {
        $content   = "\n\n\tpublic function store($requestName \$request): RedirectResponse";
        $content .= "\n\t{";
        $content  .= "\n\t\t\$data = \$request->validated();";
        $content  .= "\n\t\t\$insert = $this->modelName::create(\$data);";
        $content  .= "\n\t\tif (!\$insert) {";

        $successMessage = $this->translator->parseTranslated(
            $this->translated['success_messages']['insert'],
            [$this->modelName]
        );
        $errorMessage = $this->translator->parseTranslated(
            $this->translated['error_messages']['insert'],
            [$this->modelName]
        );

        $content  .= "\n\t\t\treturn redirect()->back()->with('error', '$errorMessage');";
        $content  .= "\n\t\t}";
        $content  .= "\n\n\t\treturn redirect()->route('index$this->modelName')->with('message', '$successMessage');";
        $content  .= "\n\t}";

        return $content;
    }

    /**
     * Add the method update to the content
     * @param string $requestName
     * @return string
     */
    private function appendUpdate(string $requestName): string
    {
        $content  = "\n\n\tpublic function update($requestName \$request, int \$id): RedirectResponse";
        $content .= "\n\t{";
        $content  .= "\n\t\t\$data = \$request->validated();";
        $content  .= "\n\n\t\t\$item = $this->modelName::find(\$id);";
        $content  .= "\n\t\t\$update = \$item->update(\$data);";
        $content  .= "\n\t\tif (!\$update) {";
        $content  .= "\n\t\t\treturn redirect()->back();";
        $content  .= "\n\t\t}";
        $content  .= "\n\n\t\treturn redirect()->route('index$this->modelName');";
        $content  .= "\n\t}";

        return $content;
    }

    /**
     * Add the method delete to the content
     * @return string
     */
    private function appendDelete(): string
    {
        $content  = "\n\n\tpublic function destroy(int \$id): RedirectResponse";
        $content .= "\n\t{";
        $content .= "\n\t\t\$item = $this->modelName::find(\$id);";
        $content  .= "\n\t\t\$delete = \$item->delete();";
        $content  .= "\n\t\tif (!\$delete) {";

        $successMessage = $this->translator->parseTranslated(
            $this->translated['success_messages']['delete'],
            [$this->modelName]
        );
        $errorMessage = $this->translator->parseTranslated(
            $this->translated['error_messages']['delete'],
            [$this->modelName]
        );

        $content  .= "\n\t\t\treturn redirect()->back()->with('error', '$errorMessage');";
        $content  .= "\n\t\t}";
        $content  .= "\n\n\t\treturn redirect()->route('index$this->modelName')->with('message', '$successMessage');";
        $content  .= "\n\t}";

        return $content;
    }
}
