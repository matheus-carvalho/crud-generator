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

    public function __construct()
    {
        $this->translator = new Translator();
        $this->utilsHelper = new Utils();
    }

    public function build(string $modelName, string $lang, array $fieldList, bool $isWithoutStyle): string
    {
        $this->translated = $this->translator->getTranslated($lang);

        $viewFolder = lcfirst($modelName);
        /** @noinspection PhpUndefinedFunctionInspection */
        $viewsPath = resource_path('views');
        $fullPath = $viewsPath . "/" . $viewFolder;

        if (!file_exists($fullPath)) {
            mkdir($viewsPath . "/" . $viewFolder);
        }

        $this->buildIndex($fullPath, $modelName, $fieldList, $isWithoutStyle);
        $this->buildCreate($fullPath, $modelName, $fieldList, $isWithoutStyle);

        return $viewFolder;
    }

    public function buildCreate(string $fullPath, string $modelName, array $fieldList, bool $isWithoutStyle)
    {
        $txtCreated = $this->translated['create'];
        $content = "";
        if (!$isWithoutStyle) {
            /** @noinspection HtmlUnknownTarget */
            $content  .= "<link href=\"{{asset('css/crudgenerator.css')}}\" rel='stylesheet'>";
        }
        $content .= "\n\n<title>$txtCreated $modelName</title>\n";
        $content .= "\n<div>";
        $content .= "\n\t<div>";
        $content .= "\n\t\t<ul class='breadcrumb'>";
        $content .= "\n\t\t\t<li><a href=\"{{ route('index$modelName') }}\">$modelName</a></li>";
        $content .= "\n\t\t\t<li class='active'>$txtCreated $modelName</li>";
        $content .= "\n\t\t</ul>";
        $content .= "\n\t</div>";
        $content .= "\n</div>";
        $content .= "\n\n<div>";
        $content .= "\n\t<form class='container' method='post' ";
        $content .= "\n\t\t@if(isset(\$item))";
        $content .= "\n\t\t\taction=\"{{ route('update$modelName', \$item->id) }}\">";
        $content .= "\n\t\t\t{!! method_field('PUT') !!}";
        $content .= "\n\t\t@else";
        $content .= "\n\t\t\taction=\"{{ route('store$modelName') }}\">";
        $content .= "\n\t\t@endif";
        $content .= "\n\t\t{!! csrf_field() !!}";

        // Fields of Model
        // Add one label and input for each item on Model List
        foreach($fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                } else {
                    $title = str_replace("_", " ", $modelItem);
                }
                $content .= "\n\t\t<div>".ucfirst($title)."</div>";
                $content .= "\n\t\t<div>";
                $content .= $this->getInputType($field, $modelItem);
                $content .= "\n\t\t</div>";
            }
        }

        $txtSave = $this->translated['save'];
        $content .= "\n\n\t\t<button class='btn btn-success'>$txtSave</button>";
        $content .= "\n\t</form>";
        $content .= "\n</div>";

        file_put_contents($fullPath."/create.blade.php", $content);
    }

    public function buildIndex(string $fullPath, string $modelName, array $fieldList, bool $isWithoutStyle)
    {
        $txtNew = $this->translated['new'];
        $txtEdit = $this->translated['edit'];
        $txtDelete = $this->translated['delete'];
        $txtDescription = $this->translated['description'];

        $content  = "";
        if (!$isWithoutStyle) {
            /** @noinspection HtmlUnknownTarget */
            $content  .= "<link href=\"{{asset('css/crudgenerator.css')}}\" rel='stylesheet'>";
        }
        $content .= "\n\n<title>$modelName</title>\n";
        $content .= "\n<div class='container'>";
        $content .= "\n\t<a href=\"{{ route('create$modelName') }}\" class='btn btn-success'> $txtNew</a>";
        $content .= "\n\n\t@if (session('message'))";
        $content .= "\n\t\t<div class='alert alert-success'>";
        $content .= "\n\t\t\t{{ session('message') }}";
        $content .= "\n\t\t</div>";
        $content .= "\n\t@endif";
        $content .= "\n\n\t<table class='table'>";
        $content .= "\n\t\t<thead>";
        $content .= "\n\t\t\t<tr>";

        $modelItems = [];
        // Add one table header for each item on Model List
        foreach($fieldList as $field) {
            $modelItem = $this->utilsHelper->getStringBetween($field, "'", "'");
            if ($modelItem != "id" && $modelItem != "") {
                if (strpos($modelItem, '_id') !== false) {
                    $title = rtrim($modelItem, "_id");
                    $title = str_replace("_", " ", $title);
                } else {
                    $title = str_replace("_", " ", $modelItem);
                }
                $content .= "\n\t\t\t\t<th>" . ucfirst($title) . "</th>";
                $modelItems[] = $modelItem;
            }
        }
        $content .= "\n\t\t\t\t<th>Ações</th>";

        $content .= "\n\t\t\t</tr>";
        $content .= "\n\t\t</thead>";
        $content .= "\n\t\t<tbody>";
        $content .= "\n\t\t\t@foreach (\$items as \$item)";
        $content .= "\n\t\t\t<tr>";
        // Add one TD for each item on Model List
        foreach ($modelItems as $modelItem) {
            if (strpos($modelItem, '_id') !== false) {
                $navigation = rtrim($modelItem, "_id");
                $navigation = str_replace('_', '', ucwords($navigation, '_'));

                $content .= "\n\t\t\t\t<td>{{\$item->".$navigation."->$txtDescription}}</td>";
            } else {
                $content .= "\n\t\t\t\t<td>{{\$item->$modelItem}}</td>";
            }
        }
        $content .= "\n\t\t\t\t<td>";
        $content .= "\n\t\t\t\t\t<a style='float: left;' href=\"{{route('edit$modelName', \$item->id)}}\" class='btn btn-warning' title='$txtEdit'>E</a>";
        $content .= "\n\t\t\t\t\t<form title='$txtDelete' method='post' action=\"{{route('delete$modelName', \$item->id)}}\">";
        $content .= "\n\t\t\t\t\t\t{!! method_field('DELETE') !!} {!! csrf_field() !!}";
        $content .= "\n\t\t\t\t\t\t<button class='btn btn-danger'> X </button>";
        $content .= "\n\t\t\t\t\t</form>";
        $content .= "\n\t\t\t\t</td>";
        $content .= "\n\t\t\t</tr>";
        $content .= "\n\t\t\t@endforeach";
        $content .= "\n\t\t</tbody>";
        $content .= "\n\t</table>";
        $content .= "\n</div>";

        file_put_contents($fullPath."/index.blade.php", $content);
    }

    public function getInputType($field, $modelItem): string
    {
        $field = $this->utilsHelper->getStringBetween($field, ">", "(");
        switch ($field) {
            case 'integer':
                return "\n\t\t\t<input type='number' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
            case 'double':
                return "\n\t\t\t<input type='number' step='0.01' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
            case 'date':
                return "\n\t\t\t<input type='date' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
            case 'dateTime':
                return "\n\t\t\t<input type='datetime-local' name='$modelItem' value=\"{{isset(\$item) ? str_replace(' ', 'T', \$item->$modelItem) : old('$modelItem')}}\">";
            case 'time':
                return "\n\t\t\t<input type='time' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
            case 'unsignedInteger':
                $PascalCaseModel = str_replace("_id", "", $modelItem);
                $ret  = "\n\t\t\t<select name='$modelItem'>";

                $txtSelect = $this->translated['select'];
                $txtDescription = $this->translated['description'];

                $ret .= "\n\t\t\t\t<option value='0'>$txtSelect ". str_replace('_', ' ', ucwords($PascalCaseModel, '_')) ."</option>";

                $PascalCaseModel = $PascalCaseModel . "s";
                $fk_array = str_replace('_', '', ucwords($PascalCaseModel, '_'));
                $fk_array = lcfirst($fk_array);
                $ret .= "\n\t\t\t\t@foreach(\$$fk_array as \$fk)";
                $ret .= "\n\t\t\t\t\t<option value=\"{{\$fk->id}}\" @if(isset(\$item) && \$fk->id == \$item->$modelItem) selected @endif>";
                $ret .= "\n\t\t\t\t\t\t{{\$fk->$txtDescription}}";
                $ret .= "\n\t\t\t\t\t</option>";
                $ret .= "\n\t\t\t\t@endforeach";
                $ret .= "\n\t\t\t</select>";
                return $ret;
            case 'string':
            default:
                return "\n\t\t\t<input type='text' name='$modelItem' value=\"{{isset(\$item) ? \$item->$modelItem : old('$modelItem')}}\">";
        }
    }
}
