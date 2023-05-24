<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

trait InitializesFields
{
    protected function initializeFields(TypeRegistry $registry, array $factories, SchemaRules $schemaRules): array {
        $initializedFields = [];

        foreach ($factories as $factory) {
            /** @var Field|InputField $fieldDeclaration */
            foreach ($factory($registry) as $fieldDeclaration) {
                if ($schemaRules->isVisible($fieldDeclaration)) {
                    $initializedFields[$fieldDeclaration->name] = $fieldDeclaration->toDefinition($schemaRules);
                }
            }
        }
        return $initializedFields;
    }

}