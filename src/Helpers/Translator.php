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
            'en' => [
                'success_messages' => [
                    'insert' => '$1 inserted successfully',
                    'delete' => '$1 deleted successfully'
                ],
                'error_messages' => [
                    'insert' => 'Error inserting $1',
                    'delete' => 'Error deleting $1'
                ],
                'create' => 'Create',
                'select' => 'Select the',
                'save' => 'Save',
                'new' => 'New',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'description' => 'name',
                'actions' => 'Actions',
                'list' => '$1 List'
            ],
            'br' => [
                'success_messages' => [
                    'insert' => 'Sucesso ao inserir $1',
                    'delete' => 'Sucesso ao deletar $1'
                ],
                'error_messages' => [
                    'insert' => 'Erro ao inserir $1',
                    'delete' => 'Erro ao deletar $1'
                ],
                'create' => 'Criar',
                'select' => 'Selecionar',
                'save' => 'Salvar',
                'new' => 'Novo',
                'edit' => 'Editar',
                'delete' => 'Deletar',
                'description' => 'nome',
                'actions' => 'Ações',
                'list' => 'Lista de $1'
            ]
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
