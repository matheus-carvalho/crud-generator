<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class ModelWorker
{
    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var array
     */
    private $fieldList;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
    }

    /**
     * Builds the Model file
     * @param string $modelName
     * @param string $tableName
     * @param array $fieldList
     * @return void
     */
    public function build(string $modelName, string $tableName, array $fieldList)
    {
        $this->fieldList = $fieldList;
        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Models/') . $modelName . ".php";

        $content = $this->appendHeaders($modelName);
        $content .= $this->appendTableName($tableName);
        $content .= $this->appendFillable();
        $content .= $this->appendNavigationProperties();
        $content .= "\n}";

        file_put_contents($filePath, $content);
    }

    /**
     * Add the headers to the content
     * @param string $modelName
     * @return string
     */
    private function appendHeaders(string $modelName): string
    {
        $content = "<?php";
        $content .= "\n\nnamespace App\Models;";
        $content .= "\n\nuse Illuminate\Database\Eloquent\Model;";
        $content .= "\n";
        $content .= "\n/**";
        $content .= "\n * @method static find(\$id)";
        $content .= "\n * @method static create(array \$data)";
        $content .= "\n */";
        $content .= "\nclass $modelName extends Model\n{\n";

        return $content;
    }

    /**
     * Add the table name to the content
     * @param string $tableName
     * @return string
     */
    private function appendTableName(string $tableName): string
    {
        return "\tprotected \$table = " . "'$tableName';";
    }

    /**
     * Add the fillable to the content
     * @return string
     */
    private function appendFillable(): string
    {
        $content = "\n\n\tprotected \$fillable = [\n";

        foreach($this->fieldList as $field) {
            $fillableItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($fillableItem != "id" && $fillableItem != "")
                $content .= "\t\t'" . $fillableItem . "',\n";
        }

        $content = rtrim($content, ",\n");
        $content .= "\n\t];";

        return $content;
    }

    /**
     * Add the navigation properties to the content
     * @return string
     */
    private function appendNavigationProperties(): string
    {
        $content = "";
        $foreignKeys = $this->utilsHelper->checkForeignKeys($this->fieldList);

        if (!$foreignKeys) {
            return $content;
        }

        foreach ($foreignKeys as $fk) {
            $array = preg_split('/(?=[A-Z])/', $fk);
            $foreignId = implode('_', $array);
            $foreignId = strtolower($foreignId);
            $foreignId = ltrim($foreignId, $foreignId[0]);
            $foreignId = $foreignId . "_id";

            $content .= "\n\n\tpublic function $fk(){";
            $content .= "\n\t\treturn \$this->belongsTo('App\\Models\\$fk', '$foreignId', 'id');";
            $content .= "\n\t}";
        }

        return $content;
    }
}
