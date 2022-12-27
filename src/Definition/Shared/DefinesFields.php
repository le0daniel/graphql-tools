<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\FieldDefinition;
use RuntimeException;

trait DefinesFields
{
    abstract protected function allFields(): array;

    private function initFields(bool $supportsLazyFields): array
    {
        $initializedFields = [];
        foreach ($this->allFields() as $key => $fieldDeclaration) {
            // Support lazy initialized fields
            if ($supportsLazyFields && is_string($key)) {
                $initializedFields[$key] = function() use ($key, $fieldDeclaration): FieldDefinition|array {
                    /** @var FieldDefinition|array|null $fieldOrInputField */
                    $fieldOrInputField = $this->initField($fieldDeclaration($key));

                    if (!$fieldOrInputField) {
                        throw new RuntimeException("Hidden fields are not supported with lazy loading, as the definition is only executed if loaded.");
                    }

                    $name = $fieldOrInputField instanceof FieldDefinition
                        ? $fieldOrInputField->name
                        : $fieldOrInputField['name'];

                    // Ensure a dynamic field does correctly have the right name. This should be checked at Build time with the validation of the schema.
                    if ($name !== $key) {
                        throw new RuntimeException("A lazy loaded field MUST have the same name as given in the array. Expected `{$key}`, got {$name}");
                    }

                    return $fieldOrInputField;
                };
                continue;
            }

            if ($declaration = $this->initField($fieldDeclaration)) {
                $initializedFields[] = $declaration;
            }
        }

        return $initializedFields;
    }
}
