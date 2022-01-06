<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

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
     * @var bool
     */
    private $isWithoutStyle;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var array
     */
    private $fieldList;

    public function __construct()
    {
        $this->translator = new Translator();
        $this->utilsHelper = new Utils();
    }

    /**
     * Builds the views files
     * @param string $modelName
     * @param string $lang
     * @param array $fieldList
     * @param bool $isWithoutStyle
     * @return string
     */
    public function build(string $modelName, string $lang, array $fieldList, bool $isWithoutStyle): string
    {
        $this->isWithoutStyle = $isWithoutStyle;
        $this->modelName = $modelName;
        $this->fieldList = $fieldList;

        $this->translated = $this->translator->getTranslated($lang);

        $viewFolder = lcfirst($modelName);
        /** @noinspection PhpUndefinedFunctionInspection */
        $viewsPath = resource_path('views');
        $fullPath = $viewsPath . "/" . $viewFolder;

        if (!file_exists($fullPath)) {
            mkdir($viewsPath . "/" . $viewFolder);
        }

        $this->buildIndex($fullPath);
        $this->buildCreate($fullPath);

        return $viewFolder;
    }

    /**
     * Builds the Index view
     * @param string $fullPath
     * @return void
     */
    public function buildIndex(string $fullPath)
    {
        $content = $this->appendStyle();
        $content .= $this->openIndexContainer();
        $content .= $this->appendIndexHeader();
        $content .= $this->appendIndexTable();
        $content .= $this->closeIndexContainer();

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
     * Appends the closing of index container
     * @return string
     */
    private function closeIndexContainer(): string
    {
        return "\n</div>";
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

        $content = "\n\t<div class=\"row justify-content-around align-items-center mt-20\">";
        $content .= "\n\t\t<div>";
        $content .= "\n\t\t\t<p class=\"list-header\">$itemList</p>";
        $content .= "\n\t\t</div>";
        $content .= "\n\t\t<div>";
        $content .= "\n\t\t\t<a href=\"{{route('create$this->modelName')}}\" class=\"btn btn-success\">$txtNew &#10004;</a>";
        $content .= "\n\t\t</div>";
        $content .= "\n\t</div>";

        $content .= "\n\t<div class=\"row\">";
        $content .= "\n\n\t@if (session('message'))";
        $content .= "\n\t\t<div class='alert alert-success'>";
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
        $txtDescription = $this->translated['description'];
        $txtActions = $this->translated['actions'];

        $content = "\n\n\t<div class=\"row\">";
        $content .= "\n\t\t<table class=\"list-table table-stripped mt-20 w-100\">";
        $content .= "\n\t\t\t<thead>";
        $content .= "\n\t\t\t\t<tr>";

        $modelItems = [];
        foreach($this->fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                } else {
                    $title = str_replace("_", " ", $modelItem);
                }
                $content .= "\n\t\t\t\t\t<th>" . ucfirst($title) . "</th>";
                $modelItems[] = $modelItem;
            }
        }
        $content .= "\n\t\t\t\t\t<th>$txtActions</th>";

        $content .= "\n\t\t\t\t</tr>";
        $content .= "\n\t\t\t</thead>";
        $content .= "\n\t\t\t<tbody>";
        $content .= "\n\t\t\t@foreach (\$items as \$item)";
        $content .= "\n\t\t\t\t<tr>";
        foreach ($modelItems as $modelItem) {
            if (strpos($modelItem, '_id') !== false) {
                $navigation = rtrim($modelItem, "_id");
                $navigation = str_replace('_', '', ucwords($navigation, '_'));

                $content .= "\n\t\t\t\t\t<td>{{\$item->".$navigation."->$txtDescription}}</td>";
            } else {
                $content .= "\n\t\t\t\t\t<td>{{\$item->$modelItem}}</td>";
            }
        }
        $content .= "\n\t\t\t\t\t<td class=\"row justify-content-start align-items-center\">";
        $content .= "\n\t\t\t\t\t\t<div class=\"action-button\">";
        $content .= "\n\t\t\t\t\t\t\t<a href=\"{{route('edit$this->modelName', \$item->id)}}\" class=\"btn btn-warning\" title=\"$txtEdit\"> &#9998; </a>";
        $content .= "\n\t\t\t\t\t\t</div>";
        $content .= "\n\t\t\t\t\t\t<div class=\"action-button\">";
        $content .= "\n\t\t\t\t\t\t\t<form title=\"$txtDelete\" method=\"post\" action=\"{{route('delete$this->modelName', \$item->id)}}\">";
        $content .= "\n\t\t\t\t\t\t\t\t{!! method_field('DELETE') !!} {!! csrf_field() !!}";
        $content .= "\n\t\t\t\t\t\t\t\t<button class=\"btn btn-danger\"> &times; </button>";
        $content .= "\n\t\t\t\t\t\t\t</form>";
        $content .= "\n\t\t\t\t\t\t</div>";
        $content .= "\n\t\t\t\t\t</td>";
        $content .= "\n\t\t\t\t</tr>";
        $content .= "\n\t\t\t@endforeach";
        $content .= "\n\t\t\t</tbody>";
        $content .= "\n\t\t</table>";
        $content .= "\n\t</div>";

        return $content;
    }

    /**
     * Builds the Create view
     * @param string $fullPath
     * @return void
     */
    public function buildCreate(string $fullPath)
    {
        $txtCreate = $this->translated['create'];

        $content = $this->appendStyle();
        $content .= $this->openCreateContainer($txtCreate);
        $content .= $this->appendCreateHeader($txtCreate);
        $content .= $this->appendCreateForm();
        $content .= $this->closeCreateContainer();

        file_put_contents($fullPath."/create.blade.php", $content);
    }

    /**
     * Appends the opening of index container
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
     * Appends the closing of index container
     * @return string
     */
    private function closeCreateContainer(): string
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
        $content = "\n\t<div class=\"mt-20\">";
        $content .= "\n\t\t<ul class=\"breadcrumb\">";
        $content .= "\n\t\t\t<li><a href=\"{{ route('index$this->modelName') }}\">$this->modelName</a></li>";
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
        $content = "\n\t<form method=\"post\" ";
        $content .= "\n\t\t@if(isset(\$item))";
        $content .= "\n\t\t\taction=\"{{ route('update$this->modelName', \$item->id) }}\">";
        $content .= "\n\t\t\t{!! method_field('PUT') !!}";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\taction=\"{{ route('store$this->modelName') }}\">";
        $content .= "\n\t\t@endif";
        $content .= "\n\t\t{!! csrf_field() !!}";

        foreach($this->fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                } else {
                    $title = str_replace("_", " ", $modelItem);
                }

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
        if ($this->isWithoutStyle) {
            return "";
        }

        /** @noinspection HtmlUnknownTarget */
        return "<link href=\"{{asset('css/crudgenerator.css')}}\" rel='stylesheet'>\n\n";
    }

    /**
     * Define which type of input field should be rendered for each field
     * @param string $field
     * @param string $modelItem
     * @param string $title
     * @return string
     */
    public function getInputType(string $field, string $modelItem, string $title): string
    {
        $field = $this->utilsHelper->getStringBetween($field, ">", "(");

        switch ($field) {
            case 'integer':
                return "\n\t\t\t<input name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\" type='number'>";
            case 'double':
                return "\n\t\t\t<input name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\" type='number' step='0.01'>";
            case 'date':
                return "\n\t\t\t<input name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\" type='date'>";
            case 'dateTime':
                return "\n\t\t\t<input name='$modelItem' value=\"{{isset(\$item) ? str_replace(' ', 'T', \$item->$modelItem) : old('$modelItem')}}\" type='datetime-local'>";
            case 'time':
                return "\n\t\t\t<input name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\" type='time'>";
            case 'string':
            default:
                $fieldHtml = "\n\t\t<div class=\"form-group\">";
                $fieldHtml .= "\n\t\t\t<label for=\"$modelItem\">$title</label>";
                $fieldHtml .= "\n\t\t\t<input class=\"form-control\" id=\"$modelItem\" name=\"$modelItem\" value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\" type=\"text\">";
                $fieldHtml .= "\n\t\t</div>";

                return $fieldHtml;
            case 'unsignedInteger':
                $PascalCaseModel = str_replace("_id", "", $modelItem);
                $content  = "\n\t\t\t<select name='$modelItem'>";

                $txtSelect = $this->translated['select'];
                $txtDescription = $this->translated['description'];

                $content .= "\n\t\t\t\t<option value='0'>$txtSelect ". str_replace('_', ' ', ucwords($PascalCaseModel, '_')) ."</option>";

                $PascalCaseModel = $PascalCaseModel . "s";
                $fkVarNames = str_replace('_', '', ucwords($PascalCaseModel, '_'));
                $fkVarNames = lcfirst($fkVarNames);
                $content .= "\n\t\t\t\t@foreach(\$$fkVarNames as \$fk)";
                $content .= "\n\t\t\t\t\t<option value=\"{{\$fk->id}}\" @if(isset(\$item) && \$fk->id == \$item->$modelItem) selected @endif>";
                $content .= "\n\t\t\t\t\t\t{{\$fk->$txtDescription}}";
                $content .= "\n\t\t\t\t\t</option>";
                $content .= "\n\t\t\t\t@endforeach";
                $content .= "\n\t\t\t</select>";

                return $content;
        }
    }
}
