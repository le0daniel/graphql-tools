<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;

trait DefinesFields
{
    private function initInputFields(array $inputFields): array
    {
        $initializedInputFields = [];
        foreach ($inputFields as $inputField) {
            if (!$inputField) {
                continue;
            }

            if (!$inputField instanceof InputField) {
                throw DefinitionException::from($inputField, InputField::class);
            }

            if ($inputField->isHidden($this->typeRegistry)) {
                continue;
            }

            $initializedInputFields[] = $inputField->toDefinition($this->typeRegistry);
        }

        return $initializedInputFields;
    }

    private function initFields(array $fieldDeclarations, bool $fieldsWithoutResolver = false): array
    {
        /** @var GraphQlType|GraphQlInterface $this */
        $initializedFields = [];
        foreach ($fieldDeclarations as $fieldDeclaration) {
            if (!$fieldDeclaration instanceof Field) {
                throw DefinitionException::from($fieldDeclaration, Field::class);
            }

            if ($fieldDeclaration->isHidden($this->typeRegistry)) {
                continue;
            }

            if ($this->typeRegistry->lazyResolveFields) {
                $initializedFields[$fieldDeclaration->name] = fn() => $fieldDeclaration->toDefinition($this->typeRegistry, $fieldsWithoutResolver);
                continue;
            }

            $initializedFields[] = $fieldDeclaration->toDefinition($this->typeRegistry, $fieldsWithoutResolver);
        }

        return $initializedFields;
    }
}
