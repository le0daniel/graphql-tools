<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

trait InitializesFields
{
    protected function initializeFields(TypeRegistry $registry, array $factories, array $excludeFieldsWithTag = []): array {
        $initializedFields = [];
        $shouldExcludeTags = !empty($excludeFieldsWithTag);

        foreach ($factories as $factory) {
            /** @var Field|InputField $fieldDeclaration */
            foreach ($factory($registry) as $fieldDeclaration) {
                if ($shouldExcludeTags && $fieldDeclaration->containsAnyOfTags(...$excludeFieldsWithTag)) {
                    continue;
                }

                $initializedFields[$fieldDeclaration->name] = $fieldDeclaration->toDefinition($excludeFieldsWithTag);
            }
        }
        return $initializedFields;
    }

}