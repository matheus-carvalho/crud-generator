<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class MigrationWorker
{
    /**
     * @var Utils
     */
    private $utilsHelper;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
    }

    /**
     * Scans migration to returns the field list and the table name
     * @param $migration
     * @return array
     */
    public function scan($migration): array
    {
        $file = 'database/migrations/'.$migration;
        $canAdd = false;
        $fieldList = [];
        $tableName = "";

        foreach (file($file) as $line)
        {
            if ($canAdd) {
                $fieldList[] = $line;
            }

            if (strpos($line, "Schema::create") !== false) {
                $canAdd = true;
                $tableName = $this->utilsHelper->getStringBetween($line, "'", "'");
            }

            if (strpos($line, "});") !== false){
                $canAdd = false;
                array_pop($fieldList);
            }
        }

        return [ $fieldList, $tableName ];
    }
}
