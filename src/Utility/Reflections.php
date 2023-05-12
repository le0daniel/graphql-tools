<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use ReflectionClass;

final class Reflections
{
    public static function setProperty(object $target, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionClass($target);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($target, $value);
    }
}