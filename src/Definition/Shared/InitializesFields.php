<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use RuntimeException;

trait InitializesFields
{
    private static function createLazyField(string $fieldName, Closure $factory, TypeRegistry $registry): Closure {
        return function() use ($fieldName, $factory, $registry): FieldDefinition|array {
            /** @var Field|InputField $fieldOrInputField */
            $fieldOrInputField = $factory($fieldName, $registry);

            // Ensure a dynamic field does correctly have the right name. This should be checked at Build time with the validation of the schema.
            if ($fieldOrInputField->name !== $fieldName) {
                throw new DefinitionException("A lazy loaded field MUST have the same name as given in the array. Expected `{$fieldName}`, got `{$fieldOrInputField->name}`");
            }

            return $fieldOrInputField->toDefinition($registry);
        };
    }

    private function createFieldsFromFactories(TypeRegistry $registry, array $factories): array {
        return array_reduce(
            $factories,
            fn(array $fields, Closure $factory): array => array_merge($fields, $factory($registry)),
            []
        );
    }

    protected function initializeFields(TypeRegistry $registry, array $factories, bool $supportsLazyFields): array {
        $initializedFields = [];

        /**
         * @var string|int $key
         * @var <Closure(string, TypeRegistry):Field|InputField>|Field|InputField $fieldDeclaration
         */
        foreach ($this->createFieldsFromFactories($registry, $factories) as $key => $fieldDeclaration) {
            if (!is_string($key)) {
                $initializedFields[] = $fieldDeclaration->toDefinition($registry);
                continue;
            }

            if (!$supportsLazyFields) {
                $typeName = $this->getName();
                throw new DefinitionException("Lazy fields are not supported for type {$typeName}.");
            }

            // Support lazy initialized fields
            if (!$fieldDeclaration instanceof Closure) {
                throw DefinitionException::from($fieldDeclaration, 'Closure');
            }

            $initializedFields[$key] = self::createLazyField($key, $fieldDeclaration, $registry);
        }

        return $initializedFields;
    }

}