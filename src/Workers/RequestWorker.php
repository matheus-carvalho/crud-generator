<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\Translator;
use Matheuscarvalho\Crudgenerator\Helpers\Utils;

class RequestWorker
{
    /**
     * @var string
     */
    private $requestName;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var array
     */
    private $fieldList;

    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var array
     */
    private $translated;

    /**
     * @param string $requestName
     * @param string $modelName
     * @param array $fieldList
     * @param string $lang
     * @return void
     */
    public function build(string $requestName, string $modelName, array $fieldList, string $lang)
    {
        $this->requestName = $requestName;
        $this->modelName = $modelName;
        $this->fieldList = $fieldList;
        $this->utilsHelper = new Utils();
        $translator = new Translator();
        $this->translated = $translator->getTranslated($lang);

        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Http/Requests/') . $requestName . ".php";

        $hasNotNullableBooleans = count($this->utilsHelper->getNotNullableBooleans($this->fieldList)) > 0;

        $content = $this->appendHeader($hasNotNullableBooleans);
        $content .= $this->appendAuthorize();

        $content .= $this->appendValidationData($hasNotNullableBooleans);
        [$rulesContent, $requiredCount, $foreignKeyCount] = $this->appendRules();
        $content .= $rulesContent;
        $content .= $this->appendMessages($requiredCount, $foreignKeyCount);

        $content .= "\n}";
        file_put_contents($filePath, $content);
    }

    /**
     * Appends the header to the content
     * @param bool $hasNotNullableBooleans
     * @return string
     */
    private function appendHeader(bool $hasNotNullableBooleans): string
    {
        $content = "<?php";
        $content .= "\n\nnamespace App\Http\Requests;\n";

        if ($hasNotNullableBooleans) {
            $content .= "\nuse App\Models\\$this->modelName;";
        }

        $content .= "\nuse Illuminate\Foundation\Http\FormRequest;";
        $content .= "\n\nclass $this->requestName extends FormRequest";
        $content .= "\n{";

        return $content;
    }

    /**
     * Appends the authorize method to the content
     * @return string
     */
    private function appendAuthorize(): string
    {
        $content = "\n\t/**";
        $content .= "\n\t * Determine if the user is authorized to make this request.";
        $content .= "\n\t *";
        $content .= "\n\t * @return bool";
        $content .= "\n\t */";
        $content .= "\n\tpublic function authorize(): bool";
        $content .= "\n\t{";
        $content .= "\n\t\treturn true;";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the validationData method to the content
     * @param bool $hasNotNullableBooleans
     * @return string
     */
    private function appendValidationData(bool $hasNotNullableBooleans): string
    {
        $content = "\n\n\tpublic function validationData(): array";
        $content .= "\n\t{";
        $content .= "\n\t\t\$data = parent::validationData();";

        if ($hasNotNullableBooleans) {
            $content .= "\n\n\t\tforeach ($this->modelName::\$notNullableBooleans as \$notNullableBoolean) {";
            $content .= "\n\t\t\t\$data[\$notNullableBoolean] = \$data[\$notNullableBoolean] ?? false;";
            $content .= "\n\t\t}";
        }

        $content .= "\n\n\t\treturn \$data;";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the rules method to the content
     * @return array
     */
    private function appendRules(): array
    {
        $content = "\n\n\t/**";
        $content .= "\n\t * Get the validation rules that apply to the request.";
        $content .= "\n\t *";
        $content .= "\n\t * @return array";
        $content .= "\n\t */";
        $content .= "\n\tpublic function rules(): array";
        $content .= "\n\t{";
        $content .= "\n\t\treturn [\n";

        $requiredCount = 0;
        [$requiredFields, $nullableFields] = $this->utilsHelper->getRequiredFields($this->fieldList);
        foreach ($requiredFields as $requiredField) {
            $content .= "\t\t\t'$requiredField' => 'required',\n";
            $requiredCount++;
        }

        foreach ($nullableFields as $nullableField) {
            $content .= "\t\t\t'$nullableField' => 'nullable',\n";
        }

        $foreignKeyCount = 0;
        foreach ($this->utilsHelper->checkForeignKeys($this->fieldList) as $foreignKey) {
            $foreignKey = lcfirst($foreignKey) . "_id";
            $content .= "\t\t\t'$foreignKey' => 'required|integer|min:1',\n";
            $foreignKeyCount++;
        }

        if (($requiredCount + $foreignKeyCount) > 0) {
            $content = rtrim($content, ",\n");
        }

        $content .= "\n\t\t];";
        $content .= "\n\t}";

        return [$content, $requiredCount, $foreignKeyCount];
    }

    /**
     * Appends the messages method to the content
     * @param int $requiredCount
     * @param int $foreignKeyCount
     * @return string
     */
    private function appendMessages(int $requiredCount, int $foreignKeyCount): string
    {
        $content = "\n\n\tpublic function messages(): array";
        $content .= "\n\t{";
        $content .= "\n\t\treturn [\n";

        $requiredMessage = $this->translated['request_messages']['required'];
        $minMessage = $this->translated['request_messages']['min'];

        if (($requiredCount + $foreignKeyCount) > 0) {
            $content .= "\t\t\t'required' => '$requiredMessage',\n";
        }

        if ($foreignKeyCount > 0) {
            $content .= "\t\t\t'min' => '$minMessage',\n";
        }

        if (($requiredCount + $foreignKeyCount) > 0) {
            $content = rtrim($content, ",\n");
        }

        $content .= "\n\t\t];";
        $content .= "\n\t}";

        return $content;
    }
}
