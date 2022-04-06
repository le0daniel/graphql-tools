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

            $definition =  $inputField->toInputFieldDefinitionArray($this->typeRepository);
            if (!$definition) {
                continue;
            }

            $initializedInputFields[] = $definition;
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

            if ($fieldDeclaration->isHidden($this->typeRepository)) {
                continue;
            }

            $initializedFields[] = $fieldDeclaration->toFieldDefinition($this->typeRepository);
            // Lazy init fields if needed.
            // $initializedFields[$fieldDeclaration->name] = fn() => $fieldDeclaration->toFieldDefinition($this->typeRepository);
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
