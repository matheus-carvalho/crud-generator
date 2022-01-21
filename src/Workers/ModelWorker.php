<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\AvailableColumnTypes;
use Matheuscarvalho\Crudgenerator\Helpers\State;
use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class ModelWorker
{
    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var State
     */
    private $state;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
        $this->state = State::getInstance();
    }

    /**
     * Builds the Model file
     * @return void
     */
    public function build()
    {
        $modelName = $this->state->getModelName();

        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Models/') . $modelName . ".php";

        $this->utilsHelper->defineForeignKeyModels();

        $content = $this->appendHeaders($modelName);
        $content .= $this->appendTableName();
        $content .= $this->appendFillable();
        $content .= $this->appendDates();

        $this->utilsHelper->defineNotNullableBooleans();
        $notNullableBooleans = $this->state->getNotNullableBooleans();

        if (count($notNullableBooleans) > 0) {
            $content .= $this->appendNotNullableBooleans($notNullableBooleans);
        }

        $content .= $this->appendNavigationProperties();
        $content .= "\n}";

        file_put_contents($filePath, $content);
    }

    /**
     * Appends the headers to the content
     * @param string $modelName
     * @return string
     */
    private function appendHeaders(string $modelName): string
    {
        $foreignKeys = $this->state->getForeignKeyModels();
        $content = "<?php";
        $content .= "\n\nnamespace App\Models;";
        $content .= "\n\nuse Illuminate\Database\Eloquent\Model;";

        if ($foreignKeys) {
            $content .= "\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;";
        }

        $content .= "\n\n/**";
        $content .= "\n * @method static find(\$id)";
        $content .= "\n * @method static create(array \$data)";
        $content .= "\n * @method static paginate(int \$perPage)";
        $content .= "\n */";
        $content .= "\nclass $modelName extends Model\n{\n";

        return $content;
    }

    /**
     * Appends the table name to the content
     * @return string
     */
    private function appendTableName(): string
    {
        $tableName = $this->state->getTableName();
        return "\tprotected \$table = " . "'$tableName';";
    }

    /**
     * Appends the fillable to the content
     * @return string
     */
    private function appendFillable(): string
    {
        $fieldList = $this->state->getFieldList();
        $content = "\n\n\tprotected \$fillable = [\n";

        foreach($fieldList as $field) {
            $fillableItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($fillableItem != "id" && $fillableItem != "") {
                $content .= "\t\t'" . $fillableItem . "',\n";
            }
        }

        $content = rtrim($content, ",\n");
        $content .= "\n\t];";

        return $content;
    }

    /**
     * Appends the dates to the content
     * @return string
     */
    private function appendDates(): string
    {
        $fieldList = $this->state->getFieldList();
        $dateColumnsTypes = [
            AvailableColumnTypes::DATETIME,
            AvailableColumnTypes::DATE,
            AvailableColumnTypes::TIME,
        ];

        $content = "\n\n\tprotected \$dates = [\n";
        foreach($fieldList as $field) {
            $type = $this->utilsHelper->getFieldType($field);
            if (!in_array($type, $dateColumnsTypes)) {
                continue;
            }

            $dateField = $this->utilsHelper->getStringBetween($field, "'", "'");
            $content .= "\t\t'$dateField',\n";
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

        foreach ($notNullableBooleans as $key => $value) {
            $value = $value ? 'true' : 'false';
            $content .= "\t\t'$key' => " . $value . ",\n";
        }

        $content = rtrim($content, ",\n");
        $content .= "\n\t];";

        return $content;
    }

    /**
     * Appends the navigation properties to the content
     * @return string
     */
    private function appendNavigationProperties(): string
    {
        $foreignKeys = $this->state->getForeignKeyModels();
        $content = "";
        if (!$foreignKeys) {
            return $content;
        }

        foreach ($foreignKeys as $fk) {
            $foreignId = $this->utilsHelper->getForeignKeyNameByModel($fk);

            $content .= "\n\n\tpublic function $fk(): BelongsTo";
            $content .= "\n\t{";
            $content .= "\n\t\treturn \$this->belongsTo('App\\Models\\$fk', '$foreignId', 'id');";
            $content .= "\n\t}";
        }

        return $content;
    }
}
