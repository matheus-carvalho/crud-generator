<?php

namespace Matheuscarvalho\Crudgenerator\Workers;

use Matheuscarvalho\Crudgenerator\Helpers\State;
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
     * @var Utils
     */
    private $utilsHelper;

    /**
     * @var array
     */
    private $translated;

    /**
     * @var State
     */
    private $state;

    /**
     * @var bool
     */
    private $hasNotNullableBooleans;

    /**
     * @var int
     */
    private $requiredCount;

    /**
     * @var int
     */
    private $foreignKeyCount;

    public function __construct()
    {
        $this->utilsHelper = new Utils();
        $this->state = State::getInstance();
    }

    /**
     * Builds the RequestWorker file
     */
    public function build()
    {
        $this->requestName = $this->state->getRequestName();
        $this->modelName = $this->state->getModelName();
        $this->translated = $this->state->getTranslated();

        /** @noinspection PhpUndefinedFunctionInspection */
        $filePath = app_path('Http/Requests/') . $this->requestName . ".php";

        $this->hasNotNullableBooleans = count($this->state->getNotNullableBooleans()) > 0;

        $content = $this->appendHeader();
        $content .= $this->appendAuthorize();

        $content .= $this->appendValidationData();
        $content .= $this->appendRules();
        $content .= $this->appendMessages();

        $content .= "\n}";
        file_put_contents($filePath, $content);
    }

    /**
     * Appends the header to the content
     * @return string
     */
    private function appendHeader(): string
    {
        $content = "<?php";
        $content .= "\n\nnamespace App\Http\Requests;\n";

        if ($this->hasNotNullableBooleans) {
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
     * @return string
     */
    private function appendValidationData(): string
    {
        $content = "\n\n\tpublic function validationData(): array";
        $content .= "\n\t{";
        $content .= "\n\t\t\$data = parent::validationData();";

        if ($this->hasNotNullableBooleans) {
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
     * @return string
     */
    private function appendRules(): string
    {
        $content = "\n\n\t/**";
        $content .= "\n\t * Get the validation rules that apply to the request.";
        $content .= "\n\t *";
        $content .= "\n\t * @return array";
        $content .= "\n\t */";
        $content .= "\n\tpublic function rules(): array";
        $content .= "\n\t{";
        $content .= "\n\t\treturn [\n";

        $this->requiredCount = 0;
        [$requiredFields, $nullableFields] = $this->utilsHelper->getRequiredFields();
        foreach ($requiredFields as $requiredField) {
            $content .= "\t\t\t'$requiredField' => 'required',\n";
            $this->requiredCount++;
        }

        $nullableCount = 0;
        foreach ($nullableFields as $nullableField) {
            $content .= "\t\t\t'$nullableField' => 'nullable',\n";
            $nullableCount++;
        }

        $this->foreignKeyCount = 0;
        foreach ($this->state->getForeignKeyModels() as $foreignKey) {
            $foreignKey = strtolower($foreignKey) . "_id";
            $content .= "\t\t\t'$foreignKey' => 'required|integer|min:1',\n";
            $this->foreignKeyCount++;
        }

        if (($this->requiredCount + $this->foreignKeyCount + $nullableCount) > 0) {
            $content = rtrim($content, ",\n");
        }

        $content .= "\n\t\t];";
        $content .= "\n\t}";

        return $content;
    }

    /**
     * Appends the messages method to the content
     * @return string
     */
    private function appendMessages(): string
    {
        $content = "\n\n\tpublic function messages(): array";
        $content .= "\n\t{";
        $content .= "\n\t\treturn [\n";

        $requiredMessage = $this->translated['request_messages']['required'];
        $minMessage = $this->translated['request_messages']['min'];

        if (($this->requiredCount + $this->foreignKeyCount) > 0) {
            $content .= "\t\t\t'required' => '$requiredMessage',\n";
        }

        if ($this->foreignKeyCount > 0) {
            $content .= "\t\t\t'min' => '$minMessage',\n";
        }

        if (($this->requiredCount + $this->foreignKeyCount) > 0) {
            $content = rtrim($content, ",\n");
        }

        $content .= "\n\t\t];";
        $content .= "\n\t}";

        return $content;
    }
}
