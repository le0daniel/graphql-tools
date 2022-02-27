<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Resolver\ProxyResolver;
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

            $field = $fieldDeclaration->toFieldDefinition($this->typeRepository);
            ProxyResolver::attachToField($field);
            $initializedFields[] = $field;
        }

        return $initializedFields;
    }

    protected function declarationToType(mixed $declaration): mixed
    {
        if (is_string($declaration)) {
            return $this->typeRepository->type($declaration);
        }

        if ($declaration instanceof Type || $declaration instanceof \Closure) {
            return $declaration;
        }

        throw new DefinitionException('Could not cast type declaration to type');
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
