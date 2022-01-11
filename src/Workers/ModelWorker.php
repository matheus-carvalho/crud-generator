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

        $foreignKeys = $this->utilsHelper->checkForeignKeys($this->fieldList);

        $content = $this->appendHeaders($modelName, $foreignKeys);
        $content .= $this->appendTableName($tableName);
        $content .= $this->appendFillable();

        $notNullableBooleans = $this->utilsHelper->getNotNullableBooleans($this->fieldList);
        if (count($notNullableBooleans) > 0) {
            $content .= $this->appendNotNullableBooleans($notNullableBooleans);
        }

        $content .= $this->appendNavigationProperties($foreignKeys);
        $content .= "\n}";

        file_put_contents($filePath, $content);
    }

    /**
     * Add the headers to the content
     * @param string $modelName
     * @param array $foreignKeys
     * @return string
     */
    private function appendHeaders(string $modelName, array $foreignKeys): string
    {
        $content = "<?php";
        $content .= "\n\nnamespace App\Models;";
        $content .= "\n\nuse Illuminate\Database\Eloquent\Model;";

        if ($foreignKeys) {
            $content .= "\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;";
        }

        $content .= "\n\n/**";
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
     * Appends the not nullable booleans to the content
     * @param array $notNullableBooleans
     * @return string
     */
    private function appendNotNullableBooleans(array $notNullableBooleans): string
    {
        $content = "\n\n\tpublic static \$notNullableBooleans = [\n";

        foreach ($notNullableBooleans as $notNullableBoolean) {
            $content .= "\t\t'$notNullableBoolean',\n";
        }

        $content = rtrim($content, ",\n");
        $content .= "\n\t];";

        return $content;
    }

    /**
     * Add the navigation properties to the content
     * @param array $foreignKeys
     * @return string
     */
    private function appendNavigationProperties(array $foreignKeys): string
    {
        $content = "";
        if (!$foreignKeys) {
            return $content;
        }

        foreach ($foreignKeys as $fk) {
            $array = preg_split('/(?=[A-Z])/', $fk);
            $foreignId = implode('_', $array);
            $foreignId = strtolower($foreignId);
            $foreignId = ltrim($foreignId, $foreignId[0]);
            $foreignId = $foreignId . "_id";

            $content .= "\n\n\tpublic function $fk(): BelongsTo";
            $content .= "\n\t{";
            $content .= "\n\t\treturn \$this->belongsTo('App\\Models\\$fk', '$foreignId', 'id');";
            $content .= "\n\t}";
        }

        return $content;
    }
}
