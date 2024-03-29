<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\AvailableColumnTypes;
use Matheuscarvalho\Crudgenerator\Helpers\State;
use Matheuscarvalho\Crudgenerator\Helpers\Translator;
use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class ViewWorker
{
    /**
     * @var array
     */
    private $translated;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var array
     */
    private $fieldList;

    /**
     * @var State
     */
    private $state;

    /**
     * @var array
     */
    private $foreignKeyFields;

    /**
     * @var array
     */
    private $modelItems;

    public function __construct()
    {
        $this->translator = new Translator();
        $this->utilsHelper = new Utils();
        $this->state = State::getInstance();
    }

    /**
     * Builds the views files
     */
    public function build()
    {
        $this->modelName = $this->state->getModelName();
        $this->fieldList = $this->state->getFieldList();
        $this->translated = $this->state->getTranslated();

        $viewFolder = $this->utilsHelper->pascalToSnake($this->modelName);
        /** @noinspection PhpUndefinedFunctionInspection */
        $viewsPath = resource_path('views');
        $fullPath = $viewsPath . "/" . $viewFolder;

        if (!file_exists($fullPath)) {
            mkdir($viewsPath . "/" . $viewFolder);
        }

        $this->defineForeignKeyFields();

        $this->buildIndex($fullPath);
        $this->buildCreate($fullPath);
        $this->buildPagination();

        $this->state->setViewFolder($viewFolder);
    }

    /**
     * Builds the Index view
     * @param string $fullPath
     * @return void
     */
    private function buildIndex(string $fullPath)
    {
        $content = $this->appendStyle();
        $content .= $this->openIndexContainer();
        $content .= $this->appendIndexHeader();
        $content .= $this->appendIndexTable();
        $content .= $this->closeContainer();

        file_put_contents($fullPath."/index.blade.php", $content);
    }

    /**
     * Appends the opening of index container
     * @return string
     */
    private function openIndexContainer(): string
    {
        $content = "<title>$this->modelName</title>\n";
        $content .= "\n<div class=\"container\">";
        return $content;
    }

    /**
     * Appends a header to the index content
     * @return string
     */
    private function appendIndexHeader(): string
    {
        $txtNew = $this->translated['new'];
        $itemList = $this->translator->parseTranslated(
            $this->translated['list'],
            [ $this->modelName ]
        );

        $createRoute = $this->state->getRoutes()['create'];

        $content = "\n\t<div class=\"row justify-content-around align-items-center mt-20\">";
        $content .= "\n\t\t<div>";
        $content .= "\n\t\t\t<p class=\"list-header\">$itemList</p>";
        $content .= "\n\t\t</div>";
        $content .= "\n\t\t<div>";
        $content .= "\n\t\t\t<a href=\"{{route('$createRoute')}}\" class=\"btn btn-success\">$txtNew &#10004;</a>";
        $content .= "\n\t\t</div>";
        $content .= "\n\t</div>";

        $content .= "\n\t<div class=\"row\">";
        $content .= "\n\n\t@if (session('message'))";
        $content .= "\n\t\t<div class='alert alert-success w-100'>";
        $content .= "\n\t\t\t{{ session('message') }}";
        $content .= "\n\t\t</div>";
        $content .= "\n\t@endif";
        $content .= "\n\t</div>";

        return $content;
    }

    /**
     * Appends a table to the index content
     * @return string
     */
    private function appendIndexTable(): string
    {
        $txtEdit = $this->translated['edit'];
        $txtDelete = $this->translated['delete'];
        $txtActions = $this->translated['actions'];
        $txtEmptyList = $this->translator->parseTranslated(
            $this->translated['empty_list'],
            [ $this->modelName ]
        );

        $editRoute = $this->state->getRoutes()['edit'];
        $deleteRoute = $this->state->getRoutes()['destroy'];

        $content = "\n\n\t<div class=\"row overflow-auto\">";
        $content .= "\n\t\t<table class=\"list-table table-stripped mt-20 w-100\">";
        $content .= "\n\t\t\t<thead>";
        $content .= "\n\t\t\t\t<tr>";

        $content .= $this->getThSessionContent();
        $content .= "\n\t\t\t\t\t<th>$txtActions</th>";

        $content .= "\n\t\t\t\t</tr>";
        $content .= "\n\t\t\t</thead>";
        $content .= "\n\t\t\t<tbody>";
        $content .= "\n\t\t\t@forelse (\$items as \$item)";
        $content .= "\n\t\t\t\t<tr>";
        foreach ($this->modelItems as $modelItem) {
            $content .= $this->getTdItemContent($modelItem);
        }
        $content .= "\n\t\t\t\t\t<td class=\"row justify-content-start align-items-center\">";
        $content .= "\n\t\t\t\t\t\t<div class=\"action-button\">";
        $content .= "\n\t\t\t\t\t\t\t<a href=\"{{route('$editRoute', \$item->id)}}\" class=\"btn btn-warning\" title=\"$txtEdit\"> &#9998; </a>";
        $content .= "\n\t\t\t\t\t\t</div>";
        $content .= "\n\t\t\t\t\t\t<div class=\"action-button\">";
        $content .= "\n\t\t\t\t\t\t\t<form title=\"$txtDelete\" method=\"post\" action=\"{{route('$deleteRoute', \$item->id)}}\">";
        $content .= "\n\t\t\t\t\t\t\t\t{!! method_field('DELETE') !!} {!! csrf_field() !!}";
        $content .= "\n\t\t\t\t\t\t\t\t<button class=\"btn btn-danger\"> &times; </button>";
        $content .= "\n\t\t\t\t\t\t\t</form>";
        $content .= "\n\t\t\t\t\t\t</div>";
        $content .= "\n\t\t\t\t\t</td>";
        $content .= "\n\t\t\t\t</tr>";
        $content .= "\n\t\t\t@empty <tr> <td colspan=\"100%\">$txtEmptyList</td> </tr>";
        $content .= "\n\t\t\t@endforelse";
        $content .= "\n\t\t\t</tbody>";
        $content .= "\n\t\t</table>";
        $content .= "\n\t\t{{\$items->links('pagination.crudgenerator')}}";
        $content .= "\n\t</div>";

        return $content;
    }

    /**
     * Builds the Create view
     * @param string $fullPath
     * @return void
     */
    private function buildCreate(string $fullPath)
    {
        $txtCreate = $this->translated['create'];

        $content = $this->appendStyle();
        $content .= $this->openCreateContainer($txtCreate);
        $content .= $this->appendCreateHeader($txtCreate);
        $content .= $this->appendCreateForm();
        $content .= $this->closeContainer();

        file_put_contents($fullPath."/create.blade.php", $content);
    }

    /**
     * Appends the opening of create container
     * @param string $txtCreate
     * @return string
     */
    private function openCreateContainer(string $txtCreate): string
    {
        $content = "<title>$txtCreate $this->modelName</title>\n";
        $content .= "\n<div class=\"container\">";

        return $content;
    }

    /**
     * Appends the closing of a container
     * @return string
     */
    private function closeContainer(): string
    {
        return "\n</div>";
    }

    /**
     * Appends a header to the creation content
     * @param string $txtCreate
     * @return string
     */
    private function appendCreateHeader(string $txtCreate): string
    {
        $indexRoute = $this->state->getRoutes()['index'];
        $content = "\n\t<div class=\"mt-20\">";
        $content .= "\n\t\t<ul class=\"breadcrumb\">";
        $content .= "\n\t\t\t<li><a href=\"{{ route('$indexRoute') }}\">$this->modelName</a></li>";
        $content .= "\n\t\t\t<li class='active'>$txtCreate $this->modelName</li>";
        $content .= "\n\t\t</ul>";
        $content .= "\n\t</div>";

        return $content;
    }

    /**
     * Appends a form to the creation content
     * @return string
     */
    private function appendCreateForm(): string
    {
        $updateRoute = $this->state->getRoutes()['update'];
        $storeRoute = $this->state->getRoutes()['store'];

        $content = "\n\t<form method=\"post\" ";
        $content .= "\n\t\t@if(isset(\$item))";
        $content .= "\n\t\t\taction=\"{{ route('$updateRoute', \$item->id) }}\">";
        $content .= "\n\t\t\t{!! method_field('PUT') !!}";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\taction=\"{{ route('$storeRoute') }}\">";
        $content .= "\n\t\t@endif";
        $content .= "\n\t\t{!! csrf_field() !!}";

        foreach($this->fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($this->shouldAddField($modelItem)) {
                $title = $modelItem;
                if ($this->isForeignKeyField($modelItem)) {
                    $title = rtrim($title, "_id");
                }
                $title = str_replace("_", " ", $title);

                $content .= $this->getInputType($field, $modelItem, ucfirst($title));
            }
        }

        $txtSave = $this->translated['save'];
        $content .= "\n\n\t\t<button class=\"btn btn-success\">$txtSave</button>";
        $content .= "\n\t</form>";

        return $content;
    }

    /**
     * Appends the style to the content
     * @return string
     */
    private function appendStyle(): string
    {
        $style = $this->state->getStyle();
        if ($style === "none") {
            return "";
        }

        /** @noinspection HtmlUnknownTarget */
        return "<link href=\"{{asset('css/crudgenerator/$style.css')}}\" rel='stylesheet'>\n\n";
    }

    /**
     * Define which type of input field should be rendered for each field
     * @param string $field
     * @param string $modelItem
     * @param string $title
     * @return string
     */
    private function getInputType(string $field, string $modelItem, string $title): string
    {
        $field = $this->utilsHelper->getStringBetween($field, ">", "(");

        $defaultHtml = "\n\t\t<div class=\"form-group\">";
        $defaultHtml .= "\n\t\t\t<label for=\"$modelItem\">$title</label>";
        $defaultHtml .= "\n\t\t\tINPUT_HTML";
        $defaultHtml .= "\n\t\t\t@error('$modelItem')";
        $defaultHtml .= "\n\t\t\t\t<div class=\"alert alert-danger\">{{ \$message }}</div>";
        $defaultHtml .= "\n\t\t\t@enderror";
        $defaultHtml .= "\n\t\t</div>";

        if ($field === AvailableColumnTypes::INTEGER) {
            $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" value=\"{{isset(\$item) ? \$item->$modelItem" .
                " : old('$modelItem')}}\" type=\"number\">";
            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::DOUBLE) {
            $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" value=\"{{isset(\$item) ? \$item->$modelItem" .
                " : old('$modelItem')}}\" type=\"number\" step=\"0.01\">";
            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::DATE) {
            $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" type=\"date\"";
            $inputHtml .= "\n\t\t\t\t\tvalue=\"{{isset(\$item) && \$item->$modelItem ? \$item->$modelItem" . "->format('Y-m-d') : old('$modelItem')}}\">";

            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::DATETIME) {
            $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" type=\"datetime-local\"";
            $inputHtml .= "\n\t\t\t\t\tvalue=\"{{isset(\$item) ? str_replace(' ', 'T', \$item->$modelItem) : old('$modelItem')}}\">";

            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::TIME) {
            $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" type=\"time\"";
            $inputHtml .= "\n\t\t\t\t\tvalue=\"{{isset(\$item) && \$item->$modelItem ? \$item->$modelItem" . "->format('h:i') : old('$modelItem')}}\">";

            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::TEXT) {
            $inputHtml = "<textarea class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\">{{isset(\$item) ?" .
                " \$item->$modelItem : old('$modelItem')}}</textarea>";
            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::BOOLEAN) {
            $inputHtml = "<input class=\"form-check-input\" id=\"$modelItem\" name=\"$modelItem\" type=\"checkbox\" value=\"true\"";
            $inputHtml .= "\n\t\t\t\t@if(isset(\$item) && \$item->$modelItem)";
            $inputHtml .= "\n\t\t\t\t\tchecked";
            $inputHtml .= "\n\t\t\t\t@endif";
            $inputHtml .= "\n\t\t\t>";

            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        if ($field === AvailableColumnTypes::FOREIGN_ID) {
            $inputHtml  = "\n\t\t\t<select name=\"$modelItem\" id=\"$modelItem\" class=\"form-control\">";

            $txtSelect = $this->translated['select'];

            $fkSnakeCased = str_replace("_id", "", $modelItem);
            $fkHumanized = $this->utilsHelper->getHumanizedForeignKeyNameByName($fkSnakeCased);
            $inputHtml .= "\n\t\t\t\t<option value=\"0\">$txtSelect $fkHumanized</option>";

            $fkLcFirstSnakeCased = lcfirst(ucwords($fkSnakeCased, "_"));
            $fkLcFirstSnakeCasedList = $fkLcFirstSnakeCased . "List";

            $fkCamelCasedList = str_replace('_', '', $fkLcFirstSnakeCasedList);
            $fkCamelCased = str_replace('_', '', $fkLcFirstSnakeCased);

            $fkCamelCasedId = $fkCamelCased . "->id";
            $fkCamelCasedName = $fkCamelCased . "->name";

            $inputHtml .= "\n\t\t\t\t@foreach(\$$fkCamelCasedList as \$$fkCamelCased)";
            $inputHtml .= "\n\t\t\t\t\t<option value=\"{{\$$fkCamelCasedId}}\"";
            $inputHtml .= "\n\t\t\t\t\t\t@if((isset(\$item) && \$$fkCamelCasedId == \$item->$modelItem) || \$$fkCamelCasedId == old('$modelItem')) selected @endif";
            $inputHtml .= "\n\t\t\t\t\t>";
            $inputHtml .= "\n\t\t\t\t\t\t{{\$$fkCamelCasedName}}";
            $inputHtml .= "\n\t\t\t\t\t</option>";
            $inputHtml .= "\n\t\t\t\t@endforeach";
            $inputHtml .= "\n\t\t\t</select>";

            return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
        }

        $inputHtml = "<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" value=\"{{isset(\$item) ?"
            . " \$item->$modelItem : old('$modelItem')}}\" type=\"text\">";
        return str_replace("INPUT_HTML", $inputHtml, $defaultHtml);
    }

    /**
     * Builds the crudgenerator (pagination) view
     * @return void
     */
    private function buildPagination()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $paginationFolder = resource_path('views') . "/pagination/";
        $paginationFile = $paginationFolder . "crudgenerator.blade.php";

        if (file_exists($paginationFile)) {
            return;
        }

        mkdir($paginationFolder);
        $content = $this->appendPagination();

        file_put_contents($paginationFile, $content);
    }

    /**
     * Appends the pagination content
     * @return string
     */
    private function appendPagination(): string
    {
        $txtPrevious = $this->translated['pagination']['previous'];
        $txtNext = $this->translated['pagination']['next'];
        $txtInfo = $this->translated['pagination']['info'];

        $content = "@if (\$paginator->hasPages())";
        $content .= "\n\t<ul class=\"pager w-100\">";
        $content .= "\n\t\t@if (\$paginator->onFirstPage())";
        $content .= "\n\t\t\t<li class=\"disabled\"><span>← $txtPrevious</span></li>";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\t<li><a href=\"{{ \$paginator->previousPageUrl() }}\" rel=\"prev\">← $txtPrevious</a></li>";
        $content .= "\n\t\t@endif";
        $content .= "\n\n\t\t@foreach (\$elements as \$element)";
        $content .= "\n\t\t\t@if (is_string(\$element))";
        $content .= "\n\t\t\t\t<li class=\"disabled\"><span>{{ \$element }}</span></li>";
        $content .= "\n\t\t\t@endif";
        $content .= "\n\n\t\t\t@if (is_array(\$element))";
        $content .= "\n\t\t\t\t@foreach (\$element as \$page => \$url)";
        $content .= "\n\t\t\t\t\t@if (\$page == \$paginator->currentPage())";
        $content .= "\n\t\t\t\t\t\t<li class=\"active my-active\"><span>{{ \$page }}</span></li>";
        $content .= "\n\t\t\t\t\t@else";
        /** @noinspection HtmlUnknownTarget */
        $content .= "\n\t\t\t\t\t\t<li><a href=\"{{ \$url }}\">{{ \$page }}</a></li>";
        $content .= "\n\t\t\t\t\t@endif";
        $content .= "\n\t\t\t\t@endforeach";
        $content .= "\n\t\t\t@endif";
        $content .= "\n\t\t@endforeach";
        $content .= "\n\n\t\t@if (\$paginator->hasMorePages())";
        $content .= "\n\t\t\t<li><a href=\"{{ \$paginator->nextPageUrl() }}\" rel=\"next\">$txtNext →</a></li>";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\t<li class=\"disabled\"><span>$txtNext →</span></li>";
        $content .= "\n\t\t@endif";
        $content .= "\n\t</ul>";
        $content .= "\n\t<small class=\"pager-info\">$txtInfo</small>";
        $content .= "\n@endif";

        return $content;
    }

    /**
     * @param string $modelItem
     * @return bool
     */
    private function shouldAddField(string $modelItem): bool
    {
        return $modelItem != "id" && $modelItem != "";
    }

    /**
     * Check if a field is foreign key
     * @param string $modelItem
     * @return bool
     */
    private function isForeignKeyField(string $modelItem): bool
    {
        return in_array($modelItem, $this->foreignKeyFields);
    }

    /**
     * Define the foreign keys
     * @return void
     */
    private function defineForeignKeyFields()
    {
        $this->foreignKeyFields = [];
        foreach($this->fieldList as $field) {
            if (!$this->utilsHelper->isForeignKey($field)) {
                continue;
            }

            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            $this->foreignKeyFields[] = $modelItem;
        }
    }

    /**
     * @param array $modelItem
     * @return string
     */
    private function getTdItemContent(array $modelItem): string
    {
        $itemName = $modelItem['name'];
        if ($this->isForeignKeyField($itemName)) {
            $navigation = rtrim($itemName, "_id");
            $navigation = str_replace('_', '', ucwords($navigation, '_'));

            return "\n\t\t\t\t\t<td>{{\$item->".$navigation."->name}}</td>";
        }

        if ($modelItem['type'] === AvailableColumnTypes::BOOLEAN) {
            $tdItemContent = "\n\t\t\t\t\t<td class=\"item {{\$item->$itemName ? 'active-item' : 'inactive-item'}}\">";
            $tdItemContent .= "\n\t\t\t\t\t\t{!! \$item->$itemName ? '&#10003;' : '&times;' !!}";
            $tdItemContent .= "\n\t\t\t\t\t</td>";

            return $tdItemContent;
        }

        $fullName = "\$item->" . $itemName;
        if ($modelItem['type'] === AvailableColumnTypes::DATE) {
            if ($this->utilsHelper->isBrazil()) {
                return "\n\t\t\t\t\t<td>{{isset($fullName) ? $fullName" . "->format('d/m/Y') : ''}}</td>";
            }

            return "\n\t\t\t\t\t<td>{{isset($fullName) ? $fullName" . "->format('Y-m-d') : ''}}</td>";
        }

        if ($modelItem['type'] === AvailableColumnTypes::TIME) {
            return "\n\t\t\t\t\t<td>{{isset($fullName) ? $fullName" . "->format('h:i') : ''}}</td>";
        }

        if ($modelItem['type'] === AvailableColumnTypes::DATETIME) {
            if ($this->utilsHelper->isBrazil()) {
                return "\n\t\t\t\t\t<td>{{isset($fullName) ? $fullName" . "->format('d/m/Y h:i') : ''}}</td>";
            }
            return "\n\t\t\t\t\t<td>{{isset($fullName) ? $fullName" . "->format('Y-m-d h:i') : ''}}</td>";
        }

        if ($modelItem['type'] === AvailableColumnTypes::DOUBLE) {
            if ($this->utilsHelper->isBrazil()) {
                return "\n\t\t\t\t\t<td>{{isset($fullName) ? number_format($fullName, 2, \",\", \".\") : ''}}</td>";
            }
            return "\n\t\t\t\t\t<td>{{isset($fullName) ? number_format($fullName, 2) : ''}}</td>";
        }

        return "\n\t\t\t\t\t<td>{{\$item->$itemName}}</td>";
    }

    /**
     * Runs through the model fields and gets the th session of table
     * @return string
     */
    private function getThSessionContent(): string
    {
        $content = "";
        foreach($this->fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            $type = $this->utilsHelper->getFieldType($field);

            if ($this->shouldAddField($modelItem)) {
                $title = $modelItem;
                if ($this->isForeignKeyField($modelItem)) {
                    $title = rtrim($title, "_id");
                }
                $title = str_replace("_", " ", $title);
                $content .= "\n\t\t\t\t\t<th>" . ucfirst($title) . "</th>";

                $this->modelItems[] = [
                    "name" => $modelItem,
                    "type" => $type
                ];
            }
        }

        return $content;
    }
}
