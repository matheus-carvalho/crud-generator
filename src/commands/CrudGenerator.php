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

        // your new stuff
        $insert = $headers;
        $insert .= $tableLine . "'$this->tableName';";
        $insert .= $fillableStart;
        // add the fields
        foreach($this->fieldList as $field) {
            $fillableItem = $this->getStringBetween($field, "'", "'");
            if ($fillableItem != "id" && $fillableItem != "")
                $insert .= "\t\t'".$fillableItem."',\n";
        }
        $insert .= $fillableEnd;
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
        $headers .= "\n\nclass $this->controllerName extends Controller\n{\n";

        // Add Methods
        $indexMethod = "\tpublic function index() { ";
        $indexMethod .= "\n\t\t\$items = $this->modelName::all();";
        $indexMethod .= "\n\t\treturn view('$this->viewFolder.index', compact('items'));";
        $indexMethod .= "\n\t}";

        $insert  = $headers;
        $insert .= $indexMethod;
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
        $content  = "<title>$this->modelName</title>\n";
        $content .= "\n<div class='container'>";
        $content .= "\n\t<a href=\"{{ route('create$this->modelName') }}\" class='btn btn-success'> Novo</a>";
        $content .= "\n\t<table class='table'>";
        $content .= "\n\t\t<tr>";

        $content .= "\n\t\t\t<th>Grupo de Assuntos</th>";

        $content .= "\n\t\t</tr>";
        $content .= "\n\t\t@foreach (\$items as \$item)";
        $content .= "\n\t\t<tr>";
        $content .= "\n\t\t\t<td>{{\$item->nome}}</td>";
        $content .= "\n\t\t</tr>";
        $content .= "\n\t\t@endforeach";
        $content .= "\n\t</table>";
        $content .= "\n</div>";

        file_put_contents($fullPath."/index.blade.php", $content);
    }

    public function createViewCreate($fullPath)
    {
        $content = 'create';
        file_put_contents($fullPath."/create.blade.php", $content);
    }

    public function getStringBetween($str,$from,$to)
    {
        $sub = substr($str, strpos($str,$from)+strlen($from),strlen($str));
        return substr($sub,0,strpos($sub,$to));
    }
}