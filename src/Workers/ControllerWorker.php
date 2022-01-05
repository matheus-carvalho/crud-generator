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

    public function __construct()
    {
        $this->utilsHelper = new Utils();
        $this->translator = new Translator();
    }

    public function build(string $controllerName, string $modelName, array $fieldList, string $viewFolder, string $lang)
    {
        $this->translated = $this->translator->getTranslated($lang);
        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Http/Controllers/') . $controllerName . ".php";

        $headers  = "<?php";
        $headers .= "\n\nnamespace App\Http\Controllers;";
        $headers .= "\n\nuse App\Models\\$modelName;";

        // check if exists foreign keys to fill the create view with model items
        $foreignKeys = $this->utilsHelper->checkForeignKeys($fieldList);
        if ($foreignKeys) {
            $fk_contents = '';
            $fk_arrays = '';
            foreach ($foreignKeys as $fk) {
                $headers .= "\nuse App\Models\\$fk;";

                $fk_array = lcfirst($fk) . 's';
                $fk_arrays .= "'$fk_array'" . ", ";
                $fk_contents .= "\n\t\t\$$fk_array = $fk::all();";
            }
        }

        $headers .= "\n\nclass $controllerName extends Controller\n{\n";

        // Add Methods
        // Index
        $indexMethod  = "\tpublic function index() { ";
        $indexMethod .= "\n\t\t\$items = $modelName::all();";
        $indexMethod .= "\n\t\treturn view('$viewFolder.index', compact('items'));";
        $indexMethod .= "\n\t}";

        // Create
        $createMethod  = "\n\n\tpublic function create() { ";
        if ($foreignKeys) {
            $createMethod .= $fk_contents;
        }
        $createMethod .= "\n\t\treturn view('$viewFolder.create'";
        if ($foreignKeys) {
            $createMethod .= ", compact(";
            $createMethod .= rtrim($fk_arrays, ", ");
            $createMethod .= ")";
        }
        $createMethod .= ");";
        $createMethod .= "\n\t}";

        // Edit
        $editMethod  = "\n\n\tpublic function edit(\$id) { ";
        $editMethod .= "\n\t\t\$item = $modelName::find(\$id);";
        if ($foreignKeys) {
            $editMethod .= $fk_contents;
        }
        $editMethod .= "\n\t\treturn view('$viewFolder.create', compact(";
        if ($foreignKeys) {
            $editMethod .= $fk_arrays;
        }
        $editMethod .= "'item'));";
        $editMethod .= "\n\t}";

        // Store
        $storeMethod   = "\n\n\tpublic function store() { ";
        $storeMethod  .= "\n\t\t\$data = request()->all();";
        $storeMethod  .= "\n\t\t\$insert = $modelName::create(\$data);";
        $storeMethod  .= "\n\t\tif (\$insert) {";

        $successMessage = $this->translator->parseTranslated(
            $this->translated['success_messages']['insert'],
            [$modelName]
        );
        $errorMessage = $this->translator->parseTranslated(
            $this->translated['error_messages']['insert'],
            [$modelName]
        );

        $storeMethod  .= "\n\t\t\treturn redirect()->route('index$modelName')->with('message', '$successMessage');";
        $storeMethod  .= "\n\t\t} else {";
        $storeMethod  .= "\n\t\t\treturn redirect()->back()->with('error', '$errorMessage');";
        $storeMethod  .= "\n\t\t}";
        $storeMethod  .= "\n\t}";

        // Update
        $updateMethod  = "\n\n\tpublic function update(\$id) { ";
        $updateMethod  .= "\n\t\t\$data = request()->all();";
        $updateMethod  .= "\n\t\t\$item = $modelName::find(\$id);";
        $updateMethod  .= "\n\t\t\$update = \$item->update(\$data);";
        $updateMethod  .= "\n\t\tif (\$update) {";
        $updateMethod  .= "\n\t\t\treturn redirect()->route('index$modelName');";
        $updateMethod  .= "\n\t\t} else {";
        $updateMethod  .= "\n\t\t\treturn redirect()->back();";
        $updateMethod  .= "\n\t\t}";
        $updateMethod  .= "\n\t}";

        // Delete
        $deleteMethod  = "\n\n\tpublic function destroy(\$id) { ";
        $deleteMethod .= "\n\t\t\$item = $modelName::find(\$id);";
        $deleteMethod  .= "\n\t\t\$delete = \$item->delete();";
        $deleteMethod  .= "\n\t\tif (\$delete) {";

        $successMessage = $this->translator->parseTranslated(
            $this->translated['success_messages']['delete'],
            [$modelName]
        );
        $errorMessage = $this->translator->parseTranslated(
            $this->translated['error_messages']['delete'],
            [$modelName]
        );

        $deleteMethod  .= "\n\t\t\treturn redirect()->route('index$modelName')->with('message', '$successMessage');";
        $deleteMethod  .= "\n\t\t} else {";
        $deleteMethod  .= "\n\t\t\treturn redirect()->back()->with('error', '$errorMessage');";
        $deleteMethod  .= "\n\t\t}";
        $deleteMethod  .= "\n\t}";

        $insert  = $headers;
        $insert .= $indexMethod;
        $insert .= $createMethod;
        $insert .= $editMethod;
        $insert .= $storeMethod;
        $insert .= $updateMethod;
        $insert .= $deleteMethod;
        $insert .= "\n}";

        file_put_contents($filePath, $insert);
    }
}
