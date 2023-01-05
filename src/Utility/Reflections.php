<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use ReflectionClass;
use RuntimeException;

final class Reflections
{
    private const PARENT_CLASSES_MAX_DEPTH = 5;

    /**
     * @param ReflectionClass $class
     * @return array<class-string>
     * @throws RuntimeException
     */
    public static function getAllParentClasses(ReflectionClass $class): array
    {
        $parentClasses = [];
        $currentDepth = 0;
        $initialClassName = $class->getName();

        while ($class = $class->getParentClass()) {
            $currentDepth++;
            $parentClasses[] = $class->getName();

            if ($currentDepth > self::PARENT_CLASSES_MAX_DEPTH) {
                $maxDepth = self::PARENT_CLASSES_MAX_DEPTH;
                throw new RuntimeException("Reached max depth of {$maxDepth} while getting all parent classes of `{$initialClassName}`");
            }
        }

        return $parentClasses;
    }

    public static function setProperty(object $target, string $propertyName, mixed $value): void
    {
        $reflection = new ReflectionClass($target);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($target, $value);
    }

}