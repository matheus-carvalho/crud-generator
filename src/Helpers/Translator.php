<?php

namespace Matheuscarvalho\Crudgenerator\Helpers;

class Translator
{
    /**
     * Provides an array of text values based on a given language
     * @param string $lang
     * @return array
     */
    public function getTranslated(string $lang): array
    {
        return [
            'en' => require_once 'lang/en.php',
            'br' => require_once 'lang/pt_BR.php'
        ][$lang];
    }

    /**
     * Replace a wildcard by a dynamic value
     * @param string $originalMessage
     * @param array $replacements
     * @return string
     */
    public function parseTranslated(string $originalMessage, array $replacements): string
    {
        $tmpMessage = $originalMessage;

        foreach ($replacements as $key => $replacement) {
            $replacementIndex = $key + 1;
            $tmpMessage = explode("$" . $replacementIndex, $originalMessage);
            $tmpMessage = implode($replacement, $tmpMessage);
        }

        return $tmpMessage;
    }
}
