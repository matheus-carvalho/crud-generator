<?php

namespace Matheuscarvalho\Crudgenerator\Helpers;

abstract class AvailableColumnTypes
{
    const STRING = "string";
    const TEXT = "text";
    const DOUBLE = "double";
    const INTEGER = "integer";
    const DATE = "date";
    const DATETIME = "dateTime";
    const TIME = "time";
    const BOOLEAN = "boolean";
    const FOREIGN_ID = "foreignId";

    public static function all(): array
    {
        return [
            self::STRING,
            self::TEXT,
            self::DOUBLE,
            self::INTEGER,
            self::DATE,
            self::DATETIME,
            self::TIME,
            self::BOOLEAN,
            self::FOREIGN_ID,
        ];
    }
}
