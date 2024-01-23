<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

trait HasFields
{
    private array $mergedFieldFactories = [];

    abstract protected function fields(TypeRegistry $registry): array;

    protected function initializeFields(TypeRegistry $registry, array $factories, SchemaRules $schemaRules): array {
        $initializedFields = [];

        foreach ($factories as $factory) {
            /** @var Field|InputField $fieldDeclaration */
            foreach ($factory($registry) as $fieldDeclaration) {
                if ($schemaRules->isVisible($fieldDeclaration)) {
                    $initializedFields[$fieldDeclaration->getName()] = fn() => $fieldDeclaration->toDefinition($registry, $schemaRules);
                }
            }
        }
        return $initializedFields;
    }

}