<?php

namespace Matheuscarvalho\Crudgenerator\Helpers;

use Exception;

class State
{
    private static $instance = null;

    private $migration = "";
    private $modelName = "";
    private $style = "";
    private $tableName = "";
    private $viewFolder = "";
    private $requestName = "";
    private $controllerName = "";

    private $paginationPerPage = 5;

    private $fieldList = [];
    private $foreignKeyModels = [];
    private $notNullableBooleans = [];
    private $translated = [];

    /**
     * @return int
     */
    public function getPaginationPerPage(): int
    {
        return $this->paginationPerPage;
    }

    /**
     * @param int $paginationPerPage
     */
    public function setPaginationPerPage(int $paginationPerPage): void
    {
        $this->paginationPerPage = $paginationPerPage;
    }

    /**
     * @return string
     */
    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    /**
     * @param string $controllerName
     */
    public function setControllerName(string $controllerName): void
    {
        $this->controllerName = $controllerName;
    }

    /**
     * @return array
     */
    public function getTranslated(): array
    {
        return $this->translated;
    }

    /**
     * @param array $translated
     */
    public function setTranslated(array $translated): void
    {
        $this->translated = $translated;
    }

    /**
     * @return string
     */
    public function getRequestName(): string
    {
        return $this->requestName;
    }

    /**
     * @param string $requestName
     */
    public function setRequestName(string $requestName): void
    {
        $this->requestName = $requestName;
    }

    /**
     * @return string
     */
    public function getViewFolder(): string
    {
        return $this->viewFolder;
    }

    /**
     * @param string $viewFolder
     */
    public function setViewFolder(string $viewFolder): void
    {
        $this->viewFolder = $viewFolder;
    }

    /**
     * @return array
     */
    public function getNotNullableBooleans(): array
    {
        return $this->notNullableBooleans;
    }

    /**
     * @param array $notNullableBooleans
     */
    public function setNotNullableBooleans(array $notNullableBooleans): void
    {
        $this->notNullableBooleans = $notNullableBooleans;
    }

    /**
     * @return array
     */
    public function getForeignKeyModels(): array
    {
        return $this->foreignKeyModels;
    }

    /**
     * @param array $foreignKeyModels
     */
    public function setForeignKeyModels(array $foreignKeyModels): void
    {
        $this->foreignKeyModels = $foreignKeyModels;
    }

    /**
     * @return array
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }

    /**
     * @param array $fieldList
     */
    public function setFieldList(array $fieldList): void
    {
        $this->fieldList = $fieldList;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    /**
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param string $modelName
     */
    public function setModelName(string $modelName): void
    {
        $this->modelName = $modelName;
    }

    /**
     * @return string
     */
    public function getMigration(): string
    {
        return $this->migration;
    }

    /**
     * @param string $migration
     */
    public function setMigration(string $migration): void
    {
        $this->migration = $migration;
    }

    private function __construct() { }
    private function __clone() { }

    /**
     * @return mixed
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): State
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}
