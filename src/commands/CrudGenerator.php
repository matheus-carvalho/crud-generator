<?php

namespace Matheuscarvalho\Crudgenerator\Src\Commands;

use Illuminate\Console\Command;

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

    private $tableName = '';
    private $modelName = '';
    private $viewFolder = '';
    private $controllerName = '';
    private $fieldList = [];
    private $language = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $migration = $this->argument('migration');
        if ($this->option('model-name')) {
            $this->modelName = $this->option('model-name');
            $this->language = $this->defineLanguage();

            $this->scanMigration($migration);
            $this->runMigration();
            $this->createModel();
            $this->createViews();
            $this->createController();
            $this->createRoutes();
        } else {
            $this->error('Not enough arguments (missing: "--model-name").');
        }
    }

    public function defineLanguage()
    {
        if (($this->option('language') === 'br') || ($this->option('language') === 'en')) {
            return $this->option('language');
        }

        return config('crudconfig.language');
    }

    public function scanMigration($migration)
    {
        $file = 'database/migrations/'.$migration;
        $canAdd = false;
        foreach (file($file) as $line)
        {
            if ($canAdd) {
                array_push($this->fieldList, $line);
            }
            if (strpos($line, "Schema::create") !== false) {
                $canAdd = true;
                $this->tableName = $this->getStringBetween($line, "'", "'");
            }
            if (strpos($line, "});") !== false){
                $canAdd = false;
                array_pop($this->fieldList);
            }
        }
    }

    public function runMigration()
    {
        $this->call('migrate');
    }

    public function createModel()
    {
        $this->call('make:model', [
            'name' => "Models/" . $this->modelName
        ]);

        $filePath = app_path('Models/') . $this->modelName . ".php";

        // Model Data
        $headers = "<?php";
        $headers .= "\n\nnamespace App\Models;";
        $headers .= "\n\nuse Illuminate\Database\Eloquent\Model;";
        $headers .= "\n\nclass $this->modelName extends Model\n{\n";

        $tableLine = "\tprotected \$table = ";
        $fillableStart = "\n\n\tprotected \$fillable = [\n";
        $fillableEnd = "\t];";

        // Your new stuff
        $insert = $headers;
        $insert .= $tableLine . "'$this->tableName';";
        $insert .= $fillableStart;

        // Add the fields
        foreach($this->fieldList as $field) {
            $fillableItem = $this->getStringBetween($field, "'", "'");
            if ($fillableItem != "id" && $fillableItem != "")
                $insert .= "\t\t'".$fillableItem."',\n";
        }
        $insert .= $fillableEnd;

        // check if exists foreign keys to make the navigation properties
        $foreignKeys = $this->checkForeignKeys();
        if ($foreignKeys) {
            foreach ($foreignKeys as $fk) {
                $array = preg_split('/(?=[A-Z])/', $fk);
                $foreign_id = implode('_', $array);
                $foreign_id = strtolower($foreign_id);
                $foreign_id = ltrim($foreign_id, $foreign_id[0]);
                $foreign_id = $foreign_id . "_id";

                $insert .= "\n\n\tpublic function $fk(){";
                $insert .= "\n\t\treturn \$this->belongsTo('App\\Models\\$fk', '$foreign_id', 'id');";
                $insert .= "\n\t}";
            }
        }

        $insert .= "\n}";

        file_put_contents($filePath, $insert);
    }

    public function createViews()
    {
        $this->viewFolder = lcfirst($this->modelName);
        $viewsPath = resource_path('views');
        $fullPath = $viewsPath."/".$this->viewFolder;
        if (file_exists($fullPath)) {
            $this->createViewIndex($fullPath);
            $this->createViewCreate($fullPath);
        } else {
            mkdir($viewsPath."/".$this->viewFolder);
            $this->createViewIndex($fullPath);
            $this->createViewCreate($fullPath);
        }
    }

    public function createController()
    {
        $this->controllerName = ucfirst($this->modelName) . "Controller";
        $this->call('make:controller', [
            'name' => $this->controllerName
        ]);

        $filePath = app_path('Http/Controllers/') . $this->controllerName . ".php";

        $headers  = "<?php";
        $headers .= "\n\nnamespace App\Http\Controllers;";
        $headers .= "\n\nuse App\Models\\$this->modelName;";

        // check if exists foreign keys to fill the create view with model items
        $foreignKeys = $this->checkForeignKeys();
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

        $headers .= "\n\nclass $this->controllerName extends Controller\n{\n";

        // Add Methods
        // Index
        $indexMethod  = "\tpublic function index() { ";
        $indexMethod .= "\n\t\t\$items = $this->modelName::all();";
        $indexMethod .= "\n\t\treturn view('$this->viewFolder.index', compact('items'));";
        $indexMethod .= "\n\t}";

        // Create
        $createMethod  = "\n\n\tpublic function create() { ";
        if ($foreignKeys) {
            $createMethod .= $fk_contents;
        }
        $createMethod .= "\n\t\treturn view('$this->viewFolder.create'";
        if ($foreignKeys) {
            $createMethod .= ", compact(";
            $createMethod .= rtrim($fk_arrays, ", ");
            $createMethod .= ")";
        }
        $createMethod .= ");";
        $createMethod .= "\n\t}";

        // Edit
        $editMethod  = "\n\n\tpublic function edit(\$id) { ";
        $editMethod .= "\n\t\t\$item = $this->modelName::find(\$id);";
        if ($foreignKeys) {
            $editMethod .= $fk_contents;
        }
        $editMethod .= "\n\t\treturn view('$this->viewFolder.create', compact(";
        if ($foreignKeys) {
            $editMethod .= $fk_arrays;
        }
        $editMethod .= "'item'));";
        $editMethod .= "\n\t}";

        // Store
        $storeMethod   = "\n\n\tpublic function store() { ";
        $storeMethod  .= "\n\t\t\$data = request()->all();";
        $storeMethod  .= "\n\t\t\$insert = $this->modelName::create(\$data);";
        $storeMethod  .= "\n\t\tif (\$insert) {";
        if ($this->language === 'en') {
            $successMessage = "$this->modelName inserted successfully";
            $errorMessage = "Error inserting $this->modelName";
        } else if ($this->language === 'br') {
            $successMessage = "Sucesso ao inserir $this->modelName";
            $errorMessage = "Erro ao inserir $this->modelName";
        }
        $storeMethod  .= "\n\t\t\treturn redirect()->route('index$this->modelName')->with('message', '$successMessage');";
        $storeMethod  .= "\n\t\t} else {";
        $storeMethod  .= "\n\t\t\treturn redirect()->back()->with('error', '$errorMessage');";
        $storeMethod  .= "\n\t\t}";
        $storeMethod  .= "\n\t}";

        // Update
        $updateMethod  = "\n\n\tpublic function update(\$id) { ";
        $updateMethod  .= "\n\t\t\$data = request()->all();";
        $updateMethod  .= "\n\t\t\$item = $this->modelName::find(\$id);";
        $updateMethod  .= "\n\t\t\$update = \$item->update(\$data);";
        $updateMethod  .= "\n\t\tif (\$update) {";
        $updateMethod  .= "\n\t\t\treturn redirect()->route('index$this->modelName');";
        $updateMethod  .= "\n\t\t} else {";
        $updateMethod  .= "\n\t\t\treturn redirect()->back();";
        $updateMethod  .= "\n\t\t}";
        $updateMethod  .= "\n\t}";

        // Delete
        $deleteMethod  = "\n\n\tpublic function destroy(\$id) { ";
        $deleteMethod .= "\n\t\t\$item = $this->modelName::find(\$id);";
        $deleteMethod  .= "\n\t\t\$delete = \$item->delete();";
        $deleteMethod  .= "\n\t\tif (\$delete) {";
        if ($this->language === 'en') {
            $successMessage = "$this->modelName deleted successfully";
            $errorMessage = "Error deleting $this->modelName";
        } else if ($this->language === 'br') {
            $successMessage = "Sucesso ao deletar $this->modelName";
            $errorMessage = "Erro ao deletar $this->modelName";
        }
        $deleteMethod  .= "\n\t\t\treturn redirect()->route('index$this->modelName')->with('message', '$successMessage');";
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

    public function createRoutes()
    {
        $routePath = base_path('routes');
        $insert  = "\nRoute::get('/$this->viewFolder', '$this->controllerName@index')->name('index$this->modelName');";
        $insert .= "\nRoute::get('/$this->viewFolder/create', '$this->controllerName@create')->name('create$this->modelName');";
        $insert .= "\nRoute::get('/$this->viewFolder/edit/{id}', '$this->controllerName@edit')->name('edit$this->modelName');";
        $insert .= "\nRoute::post('/$this->viewFolder/store', '$this->controllerName@store')->name('store$this->modelName');";
        $insert .= "\nRoute::put('/$this->viewFolder/update/{id}', '$this->controllerName@update')->name('update$this->modelName');";
        $insert .= "\nRoute::delete('/$this->viewFolder/delete/{id}', '$this->controllerName@destroy')->name('delete$this->modelName');";

        file_put_contents($routePath."/web.php", $insert, FILE_APPEND);
    }

    public function createViewIndex($fullPath)
    {
        if ($this->language === 'en') {
            $txtNew = "New";
            $txtEdit = "Edit";
            $txtDelete = "Delete";
            $txtDescription = "description";
        } else if ($this->language === 'br') {
            $txtNew = "Novo";
            $txtEdit = "Editar";
            $txtDelete = "Deletar";
            $txtDescription = "descricao";
        }
        $content  = "";
        if (!$this->option('without-style')) {
            $content  .= "<link href=\"{{asset('css/crudstyle.css')}}\" rel='stylesheet'>";
        }
        $content .= "\n\n<title>$this->modelName</title>\n";
        $content .= "\n<div class='container'>";
        $content .= "\n\t<a href=\"{{ route('create$this->modelName') }}\" class='btn btn-success'> $txtNew</a>";
        $content .= "\n\n\t@if (session('message'))";
        $content .= "\n\t\t<div class='alert alert-success'>";
        $content .= "\n\t\t\t{{ session('message') }}";
        $content .= "\n\t\t</div>";
        $content .= "\n\t@endif";
        $content .= "\n\n\t<table class='table'>";
        $content .= "\n\t\t<thead>";
        $content .= "\n\t\t\t<tr>";

        $modelItems = [];
        // Add one table header for each item on Model List
        foreach($this->fieldList as $field) {
            $modelItem = $this->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                    $content .= "\n\t\t\t\t<th>" . ucfirst($title) . "</th>";
                } else {
                    $title = str_replace("_", " ", $modelItem);
                    $content .= "\n\t\t\t\t<th>" . ucfirst($title) . "</th>";
                }
                array_push($modelItems, $modelItem);
            }
        }
        $content .= "\n\t\t\t\t<th>Ações</th>";

        $content .= "\n\t\t\t</tr>";
        $content .= "\n\t\t</thead>";
        $content .= "\n\t\t<tbody>";
        $content .= "\n\t\t\t@foreach (\$items as \$item)";
        $content .= "\n\t\t\t<tr>";
        // Add one TD for each item on Model List
        foreach ($modelItems as $modelItem) {
            if (strpos($modelItem, '_id') !== false) {
                $navigation = rtrim($modelItem, "_id");
                $navigation = str_replace('_', '', ucwords($navigation, '_'));

                $content .= "\n\t\t\t\t<td>{{\$item->".$navigation."->$txtDescription}}</td>";
            } else {
                $content .= "\n\t\t\t\t<td>{{\$item->$modelItem}}</td>";
            }
        }
        $content .= "\n\t\t\t\t<td>";
        $content .= "\n\t\t\t\t\t<a style='float: left;' href=\"{{route('edit$this->modelName', \$item->id)}}\" class='btn btn-warning' title='$txtEdit'>E</a>";
        $content .= "\n\t\t\t\t\t<form title='$txtDelete' method='post' action=\"{{route('delete$this->modelName', \$item->id)}}\">";
        $content .= "\n\t\t\t\t\t\t{!! method_field('DELETE') !!} {!! csrf_field() !!}";
        $content .= "\n\t\t\t\t\t\t<button class='btn btn-danger'> X </button>";
        $content .= "\n\t\t\t\t\t</form>";
        $content .= "\n\t\t\t\t</td>";
        $content .= "\n\t\t\t</tr>";
        $content .= "\n\t\t\t@endforeach";
        $content .= "\n\t\t</tbody>";
        $content .= "\n\t</table>";
        $content .= "\n</div>";

        file_put_contents($fullPath."/index.blade.php", $content);
    }

    public function createViewCreate($fullPath)
    {
        if ($this->language === 'en') {
            $txtCreate = "Create";
            $txtSelect = "Select the";
            $txtSave = "Save";
        } else if ($this->language === 'br') {
            $txtCreate = "Criar";
            $txtSelect = "Selecionar";
            $txtSave = "Salvar";
        }
        $content = "";
        if (!$this->option('without-style')) {
            $content  .= "<link href=\"{{asset('css/crudstyle.css')}}\" rel='stylesheet'>";
        }
        $content .= "\n\n<title>$txtCreate $this->modelName</title>\n";
        $content .= "\n<div>";
        $content .= "\n\t<div>";
        $content .= "\n\t\t<ul class='breadcrumb'>";
        $content .= "\n\t\t\t<li><a href=\"{{ route('index$this->modelName') }}\">$this->modelName</a></li>";
        $content .= "\n\t\t\t<li class='active'>$txtCreate $this->modelName</li>";
        $content .= "\n\t\t</ul>";
        $content .= "\n\t</div>";
        $content .= "\n</div>";
        $content .= "\n\n<div>";
        $content .= "\n\t<form class='container' method='post' ";
        $content .= "\n\t\t@if(isset(\$item))";
        $content .= "\n\t\t\taction=\"{{ route('update$this->modelName', \$item->id) }}\">";
        $content .= "\n\t\t\t{!! method_field('PUT') !!}";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\taction=\"{{ route('store$this->modelName') }}\">";
        $content .= "\n\t\t@endif";
        $content .= "\n\t\t{!! csrf_field() !!}";

        // Fields of Model
        // Add one label and input for each item on Model List
        foreach($this->fieldList as $field) {
            $modelItem = $this->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                    $content .= "\n\t\t<div>".ucfirst($title)."</div>";
                } else {
                    $title = str_replace("_", " ", $modelItem);
                    $content .= "\n\t\t<div>".ucfirst($title)."</div>";
                }
                $content .= "\n\t\t<div>";
                $content .= $this->getInputType($field, $modelItem);
                $content .= "\n\t\t</div>";
            }
        }

        $content .= "\n\n\t\t<button class='btn btn-success'>$txtSave</button>";
        $content .= "\n\t</form>";
        $content .= "\n</div>";

        file_put_contents($fullPath."/create.blade.php", $content);
    }

    public function getStringBetween($str,$from,$to)
    {
        $sub = substr($str, strpos($str,$from)+strlen($from),strlen($str));
        return substr($sub,0,strpos($sub,$to));
    }

    public function getInputType($field, $modelItem)
    {
        //$modelItem = category_id
        //$field = $table->unsignedInteger('category_id')->nullable();
        $field = $this->getStringBetween($field, ">", "(");
        switch ($field) {
            case 'integer':
                return "\n\t\t\t<input type='number' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
            case 'double':
                return "\n\t\t\t<input type='number' step='0.01' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
            case 'string':
                return "\n\t\t\t<input type='text' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
            case 'date':
                return "\n\t\t\t<input type='date' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
            case 'dateTime':
                return "\n\t\t\t<input type='datetime-local' name='$modelItem' value=\"{{isset(\$item) ? str_replace(' ', 'T', \$item->$modelItem) : old('$modelItem')}}\">";
                break;
            case 'time':
                return "\n\t\t\t<input type='time' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
            case 'unsignedInteger':
                $PascalCaseModel = str_replace("_id", "", $modelItem);
                $ret  = "\n\t\t\t<select name='$modelItem'>";

                if ($this->language === 'en') {
                    $txtSelect = "Select the";
                    $txtDescription = "description";
                } else if ($this->language === 'br') {
                    $txtSelect = "Selecionar";
                    $txtDescription = "descricao";
                }
                $ret .= "\n\t\t\t\t<option value='0'>$txtSelect ". str_replace('_', ' ', ucwords($PascalCaseModel, '_')) ."</option>";

                $PascalCaseModel = $PascalCaseModel . "s";
                $fk_array = str_replace('_', '', ucwords($PascalCaseModel, '_'));
                $fk_array = lcfirst($fk_array);
                $ret .= "\n\t\t\t\t@foreach(\$$fk_array as \$fk)";
                $ret .= "\n\t\t\t\t\t<option value=\"{{\$fk->id}}\" @if(isset(\$item) && \$fk->id == \$item->$modelItem) selected @endif>";
                $ret .= "\n\t\t\t\t\t\t{{\$fk->$txtDescription}}";
                $ret .= "\n\t\t\t\t\t</option>";
                $ret .= "\n\t\t\t\t@endforeach";
                $ret .= "\n\t\t\t</select>";
                return $ret;
                break;
            default:
                return "\n\t\t\t<input type='text' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
                break;
        }
    }

    public function checkForeignKeys()
    {
        $models = [];
        foreach ($this->fieldList as $field) {
            $type = $this->getStringBetween($field, ">", "(");
            if ($type == 'unsignedInteger') {
                $modelName = $this->getStringBetween($field, "'", "'");
                $modelName = rtrim($modelName, '_id');
                $modelName = str_replace('_', '', ucwords($modelName, '_'));
                array_push($models, $modelName);
            }
        }
        return $models;
    }
}