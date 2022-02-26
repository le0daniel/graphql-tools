<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
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
        $fields = [];
        foreach ($inputFields as $name => $inputField) {
            if (!$inputField) {
                continue;
            }

            if (is_array($inputField)) {
                $fields[] = [
                    'name' => $name,
                    'type' => $this->declarationToType($inputField)
                ];
                continue;
            }

            if (!$inputField instanceof InputField) {
                $className = is_object($inputField) ? get_class($inputField) : gettype($inputField);
                throw new RuntimeException("Expected InputField, got instance of '{$className}'");
            }

            $fields[] = $inputField->toInputFieldDefinitionArray($this->typeRepository);
        }

        return $fields;
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
        foreach ($allDeclaredFields as $fieldName => $fieldDeclaration) {
            if (!$fieldDeclaration) {
                continue;
            }

            // Attaches the field type from a given type name
            $field = $this->createField($fieldName, $fieldDeclaration);
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

    private function createFieldFromString(string $className, mixed $name): FieldDefinition {
        return FieldDefinition::create([
            'name' => $name,
            'type' => $this->declarationToType($className),
            'resolve' => new ProxyResolver()
        ]);
    }

    /**
     * @throws DefinitionException
     */
    private function createField(mixed $name, mixed $fieldDeclaration): FieldDefinition
    {
        if ($fieldDeclaration instanceof FieldDefinition) {
            return $fieldDeclaration;
        }

        if ($fieldDeclaration instanceof GraphQlField) {
            return $fieldDeclaration->toFieldDefinition(
                $this->typeRepository
            );
        }

        if (is_string($fieldDeclaration)) {
            return $this->createFieldFromString($fieldDeclaration, $name);
        }

        if (is_array($fieldDeclaration)) {
            $fieldDeclaration['name'] = $name;
            $fieldDeclaration['type'] = $this->declarationToType($fieldDeclaration['type']);
            $fieldDeclaration['resolve'] = $fieldDeclaration['resolve'] ?? new ProxyResolver();
            $fieldDeclaration['args'] = $this->initInputFields($fieldDeclaration['args'] ?? []);
            return FieldDefinition::create($fieldDeclaration);
        }

        if ($fieldDeclaration instanceof Type) {
            return FieldDefinition::create([
                'name' => $name,
                'type' => $fieldDeclaration,
                'resolve' => new ProxyResolver(),
            ]);
        }

        throw new DefinitionException('Could not create field based on your definition');
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
