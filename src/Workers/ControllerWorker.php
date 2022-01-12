<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\State;
use Matheuscarvalho\Crudgenerator\Helpers\Translator;

class ControllerWorker
{
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

    /**
     * @var string
     */
    private $requestName;

    /**
     * @var State
     */
    private $state;

    /**
     * @var string
     */
    private $fkQueryContent;

    /**
     * @var string
     */
    private $compactContent;

    public function __construct()
    {
        $this->translator = new Translator();
        $this->state = State::getInstance();
    }

    /**
     * Builds the Controller file
     */
    public function build()
    {
        $this->modelName = $this->state->getModelName();
        $this->viewFolder = $this->state->getViewFolder();
        $this->controllerName = $this->state->getControllerName();
        $this->translated = $this->state->getTranslated();
        $this->requestName = $this->state->getRequestName();

        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Http/Controllers/') . $this->controllerName . ".php";

        $foreignKeys = $this->state->getForeignKeyModels();

        $content = $this->appendHeaders($foreignKeys);
        $content .= $this->appendIndex();
        $content .= $this->appendCreate($foreignKeys);
        $content .= $this->appendEdit($foreignKeys);
        $content .= $this->appendStore();
        $content .= $this->appendUpdate();
        $content .= $this->appendDelete();

        $content .= "\n}";
        file_put_contents($filePath, $content);
    }

    /**
     * Add the headers to the content and prepare the foreign keys
     * @param array $foreignKeys
     * @return string
     */
    private function appendHeaders(array $foreignKeys): string
    {
        $content = "<?php";
        $content .= "\n\nnamespace App\Http\Controllers;";
        $content .= "\n\nuse App\Http\Requests\\$this->requestName;";
        $content .= "\nuse Illuminate\Http\RedirectResponse;";
        $content .= "\nuse Illuminate\View\View;";
        $content .= "\nuse App\Models\\$this->modelName;";

        $this->compactContent = '';
        $this->fkQueryContent = '';
        if ($foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $content .= "\nuse App\Models\\$fk;";

                $fkNameCompact = lcfirst($fk) . 'List';
                $this->compactContent .= "'$fkNameCompact'" . ", ";
                $this->fkQueryContent .= "\n\t\t\$$fkNameCompact = $fk::all();";
            }
        }

        $content .= "\n\nclass $this->controllerName extends Controller\n{\n";

        return $content;
    }

    /**
     * Appends the method index to the content
     * @return string
     */
    private function appendIndex(): string
    {
        $paginationPerPage = $this->state->getPaginationPerPage();

        $content = "\tpublic function index(): View";
        $content .= "\n\t{";
        $content .= "\n\t\t\$perPage = $paginationPerPage;";
        $content .= "\n\t\t\$items = $this->modelName::paginate(\$perPage);";
        $content .= "\n\t\treturn view('$this->viewFolder.index', compact('items'));";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the method create to the content
     * @param array $foreignKeys
     * @return string
     */
    private function appendCreate(array $foreignKeys): string
    {
        $content = "\n\n\tpublic function create(): View";
        $content .= "\n\t{";
        if ($foreignKeys) {
            $content .= $this->fkQueryContent;
        }
        $content .= "\n\t\treturn view('$this->viewFolder.create'";
        if ($foreignKeys) {
            $content .= ", compact(";
            $content .= rtrim($this->compactContent, ", ");
            $content .= ")";
        }
        $content .= ");";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the method edit to the content
     * @param array $foreignKeys
     * @return string
     */
    private function appendEdit(array $foreignKeys): string
    {
        $content  = "\n\n\tpublic function edit(\$id): View";
        $content .= "\n\t{";
        $content .= "\n\t\t\$item = $this->modelName::find(\$id);";
        if ($foreignKeys) {
            $content .= $this->fkQueryContent;
        }
        $content .= "\n\t\treturn view('$this->viewFolder.create', compact(";
        if ($foreignKeys) {
            $content .= $this->compactContent;
        }
        $content .= "'item'));";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the method store to the content
     * @return string
     */
    private function appendStore(): string
    {
        $content   = "\n\n\tpublic function store($this->requestName \$request): RedirectResponse";
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
     * Appends the method update to the content
     * @return string
     */
    private function appendUpdate(): string
    {
        $content  = "\n\n\tpublic function update($this->requestName \$request, int \$id): RedirectResponse";
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
     * Appends the method delete to the content
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
