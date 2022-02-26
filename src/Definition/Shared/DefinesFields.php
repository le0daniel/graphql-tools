<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\FieldDefinition;
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

    /**
     * Ensures the Input fields are correctly defined
     *
     * @param array $inputFields
     * @return array
     * @throws DefinitionException
     */
    private function initInputFields(array $inputFields): array
    {
        $initializedInputFields = [];
        foreach ($inputFields as $inputField) {
            if (!$inputField) {
                continue;
            }

            if (!$inputField instanceof InputField || !$inputField instanceof Argument) {
                $className = is_object($inputField) ? get_class($inputField) : gettype($inputField);
                throw new RuntimeException("Expected InputField or Argument, got {$className}");
            }

            $initializedInputFields[] = $inputField->toInputFieldDefinitionArray($this->typeRepository);
        }

        return $initializedInputFields;
    }

    /**
     * Ensures the fields have the proxy attached to the resolve function
     *
     * @return array
     * @throws DefinitionException
     */
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
                $className = is_object($fieldDeclaration) ? get_class($fieldDeclaration) : gettype($fieldDeclaration);
                throw new RuntimeException("Expected GraphQlField, got {$className}");
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

    /**
     * This is used internally for appending fields automatically to the root query type.
     *
     * @param FieldDefinition ...$fields
     * @return void
     */
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
