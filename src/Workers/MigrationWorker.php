<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\State;
use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class MigrationWorker
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Utils
     */
    private $utilsHelper;

    public function __construct()
    {
        $this->state = State::getInstance();
        $this->utilsHelper = new Utils();
    }

    /**
     * @return bool
     */
    public function scan(): bool
    {
        $migration = $this->state->getMigration();
        $filePath = "database/migrations/*_create_" . $migration . "_table.php*";
        $file = glob($filePath)[0] ?? null;
        if (!$file) {
            return false;
        }

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

        $this->state->setFieldList($fieldList);
        $this->state->setTableName($tableName);
        return true;
    }
}
