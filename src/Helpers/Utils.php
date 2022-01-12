<?php

namespace Matheuscarvalho\Crudgenerator\Helpers;

class Utils
{
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
     * Checks if exists foreign keys and if so, return correspondent models
     * @param array $fieldList
     * @return array
     */
    public function checkForeignKeys(array $fieldList): array
    {
        $models = [];

        foreach ($fieldList as $field) {
            $type = $this->getStringBetween($field, "\$table->", "(");
            if ($type !== AvailableColumnTypes::FOREIGN_ID) {
                continue;
            }

            $modelName = $this->getStringBetween($field, "'", "'");
            $modelName = rtrim($modelName, '_id');
            $modelName = str_replace('_', '', ucwords($modelName, '_'));
            $models[] = $modelName;
        }
        return $models;
    }

    /**
     * Get all boolean fields that's not nullable
     * @param array $fieldList
     * @return array
     */
    public function getNotNullableBooleans(array $fieldList): array
    {
        $booleans = [];

        foreach ($fieldList as $field) {
            $type = $this->getStringBetween($field, "\$table->", "(");
            if ($type !== AvailableColumnTypes::BOOLEAN) {
                continue;
            }

            if (strpos($field, "->nullable()") === false) {
                $modelName = $this->getStringBetween($field, "'", "'");
                $booleans[] = $modelName;
            }
        }

        return $booleans;
    }

    /**
     * Get all required fields
     * @param array $fieldList
     * @return array
     */
    public function getRequiredFields(array $fieldList): array
    {
        $requiredFields = [];
        $nullableFields = [];

        foreach ($fieldList as $field) {
            if ($this->isReservedField($field)) {
                continue;
            }

            $type = $this->getStringBetween($field, "\$table->", "(");
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
}
