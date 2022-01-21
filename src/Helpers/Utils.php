<?php

namespace Matheuscarvalho\Crudgenerator\Helpers;

class Utils
{
    /**
     * @var State
     */
    private $state;

    public function __construct()
    {
        $this->state = State::getInstance();
    }

    /**
     * Return a part of string between two characters
     * @param string $str
     * @param string $from
     * @param string $to
     * @return string
     */
    public function getStringBetween(string $str, string $from, string $to): string
    {
        if ($from === $to && ($from === "'" || $from === '"')) {
            return $this->getStringBetweenQuotes($str);
        }

        $sub = substr($str, strpos($str,$from) + strlen($from), strlen($str));
        return substr($sub,0, strpos($sub, $to));
    }

    private function getStringBetweenQuotes(string $str): string
    {
        $sub = substr($str, strpos($str, "'") + strlen("'"), strlen($str));
        $quoted = substr($sub,0, strpos($sub, "'"));
        if (!$quoted) {
            $sub = substr($str, strpos($str, '"') + strlen('"'), strlen($str));
            $quoted = substr($sub,0, strpos($sub, '"'));
        }

        return $quoted;
    }

    /**
     * Define and store to state the foreign key models
     */
    public function defineForeignKeyModels()
    {
        $fieldList = $this->state->getFieldList();
        $models = [];

        foreach ($fieldList as $field) {
            if (!$this->isForeignKey($field)) {
                continue;
            }

            $modelName = $this->getStringBetween($field, "'", "'");
            $modelName = rtrim($modelName, '_id');
            $modelName = str_replace('_', '', ucwords($modelName, '_'));
            $models[] = $modelName;
        }

        $this->state->setForeignKeyModels($models);
    }

    /**
     * Define and store to state all boolean fields that's not nullable
     */
    public function defineNotNullableBooleans()
    {
        $fieldList = $this->state->getFieldList();
        $booleans = [];

        foreach ($fieldList as $field) {
            $type = $this->getFieldType($field);
            if ($type !== AvailableColumnTypes::BOOLEAN) {
                continue;
            }

            if (strpos($field, "->nullable()") === false) {
                $modelName = $this->getStringBetween($field, "'", "'");
                $defaultValue = strpos($field, "->default(true)") !== false;
                $booleans[$modelName] = $defaultValue;
            }
        }

        $this->state->setNotNullableBooleans($booleans);
    }

    /**
     * Get all required and nullable fields
     * @return array
     */
    public function getRequiredFields(): array
    {
        $fieldList = $this->state->getFieldList();
        $requiredFields = [];
        $nullableFields = [];

        foreach ($fieldList as $field) {
            if ($this->isReservedField($field)) {
                continue;
            }

            $type = $this->getFieldType($field);
            if (!in_array($type, AvailableColumnTypes::all()) || $type === AvailableColumnTypes::FOREIGN_ID) {
                continue;
            }

            $modelName = $this->getStringBetween($field, "'", "'");
            if (strpos($field, "->nullable()") === false) {
                $requiredFields[] = $modelName;
            } else {
                $nullableFields[] = $modelName;
            }
        }

        return [$requiredFields, $nullableFields];
    }

    /**
     * Check if field is reserved
     * @param string $field
     * @return bool
     */
    private function isReservedField(string $field): bool
    {
        if (strpos($field, "\$table->id();") === false && strpos($field, "\$table->timestamps();") === false) {
            return false;
        }

        return true;
    }

    /**
     * Parse a snake_case string to kebab-case
     * @param string $snaked
     * @return string
     */
    public function snakeToKebab(string $snaked): string
    {
        return implode("-", explode("_", $snaked));
    }

    /**
     * Parse a PascalCase string to snake_case
     * @param string $string
     * @return string
     */
    public function pascalToSnake(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * Check if a field is foreign key
     * @param string $field
     * @return bool
     */
    public function isForeignKey(string $field): bool
    {
        $type = $this->getFieldType($field);
        return $type === AvailableColumnTypes::FOREIGN_ID;
    }

    /**
     * Check if selected language is pt br
     * @return bool
     */
    public function isBrazil(): bool
    {
        return $this->state->getLanguage() === "br";
    }

    /**
     * Defines the type of field
     * @param string $field
     * @return string
     */
    public function getFieldType(string $field): string
    {
        return $this->getStringBetween($field, "\$table->", "(");
    }

    /**
     * Gets the foreign key name based on a foreign key model
     * @param string $fkModel
     * @return string
     */
    public function getForeignKeyNameByModel(string $fkModel): string
    {
        $split = preg_split('/(?=[A-Z])/', $fkModel);
        $fkName = implode('_', $split);
        $fkName = strtolower($fkName);
        $fkName = ltrim($fkName, $fkName[0]);
        return $fkName . "_id";
    }

    /**
     * Return the foreign key humanized name
     * @param string $fkName
     * @return string
     */
    public function getHumanizedForeignKeyNameByName(string $fkName): string
    {
        $humanized = ucwords(rtrim($fkName, "_id"), "_");
        return str_replace('_', ' ', $humanized);
    }
}
