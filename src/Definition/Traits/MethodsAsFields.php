<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Traits;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use ReflectionClass;
use ReflectionMethod;
use GraphQlTools\Definition\Attributes\Field as FieldAttribute;

/**
 * Trait to use more syntax sugar for defining fields on types
 *
 * It allows you to define fields are static methods, which should be public.
 * The need at least to implement the ofType return type
 */
trait MethodsAsFields
{

    final protected function fields(TypeRegistry $registry): array
    {
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);

        $fields = [];
        foreach ($methods as $method) {
            /** @var FieldAttribute $fieldDefinition */
            $fieldDefinition = $method->getAttributes(FieldAttribute::class)[0]?->newInstance();

            if (!$fieldDefinition) {
                continue;
            }

            $fields[] = Field::withName($method->getName())
                ->ofType($fieldDefinition->getType($registry, $method))
                ->resolvedBy($method->getClosure());
        }

        return $fields;
    }
}