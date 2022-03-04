<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Definition\Field\GraphQlField;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

abstract class TypeTestCase extends TestCase
{

    abstract protected function typeClassName(): string;

    protected function getFieldByName(string $fieldName): GraphQlField {
        $typeReflection = new ReflectionClass($this->typeClassName());
        $type = $typeReflection->newInstanceWithoutConstructor();

        $fieldsMethod = $typeReflection->getMethod('fields');
        $fieldsMethod->setAccessible(true);

        /** @var GraphQlField $field */
        foreach ($fieldsMethod->invoke($type) as $field) {
            if ($field->name === $fieldName) {
                return $field;
            }
        }

        throw new RuntimeException("Could not find field with name '{$fieldName}' on type '{$this->typeClassName()}'");
    }

}