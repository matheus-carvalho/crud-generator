<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class ModelWorker
{
    /**
     * @var Utils
     */
    private $utilsHelper;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
    }

    public function build(string $modelName, string $tableName, array $fieldList)
    {
        $filePath = app_path('Models/') . $modelName . ".php";

        // Model Data
        $headers = "<?php";
        $headers .= "\n\nnamespace App\Models;";
        $headers .= "\n\nuse Illuminate\Database\Eloquent\Model;";
        $headers .= "\n\nclass $modelName extends Model\n{\n";

        $tableLine = "\tprotected \$table = ";
        $fillableStart = "\n\n\tprotected \$fillable = [\n";
        $fillableEnd = "\t];";

        // Your new stuff
        $insert = $headers;
        $insert .= $tableLine . "'$tableName';";
        $insert .= $fillableStart;

        // Add the fields
        foreach($fieldList as $field) {
            $fillableItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($fillableItem != "id" && $fillableItem != "")
                $insert .= "\t\t'".$fillableItem."',\n";
        }
        $insert .= $fillableEnd;

        // check if exists foreign keys to make the navigation properties
        $foreignKeys = $this->utilsHelper->checkForeignKeys($fieldList);
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
}
