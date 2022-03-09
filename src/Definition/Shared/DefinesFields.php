<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\ProxyResolver;
use RuntimeException;

trait DefinesFields
{
    private bool $fieldAreInitialized = false;
    private array $fieldsToAppend = [];

    private function initInputFields(array $inputFields): array
    {
        $initializedInputFields = [];
        foreach ($inputFields as $inputField) {
            if (!$inputField) {
                continue;
            }

            if (!$inputField instanceof InputField) {
                throw DefinitionException::from($inputField, InputField::class, Argument::class);
            }

            $initializedInputFields[] = $inputField->toInputFieldDefinitionArray($this->typeRepository);
        }

        return $initializedInputFields;
    }

    private function initFields(array $fields): array
    {
        $this->fieldAreInitialized = true;
        $allDeclaredFields = array_merge($fields, $this->fieldsToAppend);

        /** @var GraphQlType|GraphQlInterface $this */
        $initializedFields = [];
        foreach ($allDeclaredFields as $fieldDeclaration) {
            if (!$fieldDeclaration) {
                continue;
            }

            if (!$fieldDeclaration instanceof GraphQlField) {
                throw DefinitionException::from($fieldDeclaration, GraphQlField::class);
            }

            if ($fieldDefinition = $fieldDeclaration->toFieldDefinition($this->typeRepository)) {
                $initializedFields[] = $fieldDefinition;
            }
        }

        return $initializedFields;
    }

    final public function appendField(GraphQlField $field): void {
        if ($this->fieldAreInitialized) {
            throw new RuntimeException(implode(PHP_EOL, [
                "You can not append fields if the fields are already initialized.",
                "THIS METHOD is intended for internal functionality ONLY: DO NOT USE THIS."
            ]));
        }

        $this->fieldsToAppend[] = $field;
    }

}
