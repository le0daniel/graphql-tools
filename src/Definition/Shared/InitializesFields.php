<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

trait InitializesFields
{
    private function createFieldsFromFactories(TypeRegistry $registry, array $factories): array {
        $fields = [];
        foreach ($factories as $factory) {
            array_push($fields, ...$factory($registry));
        }
        return $fields;
    }

    protected function initializeFields(TypeRegistry $registry, array $factories, array $excludeFieldsWithTag = []): array {
        $initializedFields = [];
        $shouldExcludeTags = !empty($excludeFieldsWithTag);

        /**
         * @var string|int $key
         * @var <Closure(string, TypeRegistry):Field|InputField>|Field|InputField $fieldDeclaration
         */
        foreach ($this->createFieldsFromFactories($registry, $factories) as $fieldDeclaration) {
            // Skip based on tags
            if ($shouldExcludeTags && $fieldDeclaration->containsAnyOfTags(...$excludeFieldsWithTag)) {
                continue;
            }

            $initializedFields[$fieldDeclaration->name] = $fieldDeclaration->toDefinition($registry, $excludeFieldsWithTag);
        }

        return $initializedFields;
    }

}