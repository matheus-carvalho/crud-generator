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
        $sub = substr($str, strpos($str,$from) + strlen($from), strlen($str));
        return substr($sub,0, strpos($sub, $to));
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
            $type = $this->getStringBetween($field, ">", "(");
            if ($type == 'unsignedInteger') {
                $modelName = $this->getStringBetween($field, "'", "'");
                $modelName = rtrim($modelName, '_id');
                $modelName = str_replace('_', '', ucwords($modelName, '_'));
                $models[] = $modelName;
            }
        }
        return $models;
    }
}
