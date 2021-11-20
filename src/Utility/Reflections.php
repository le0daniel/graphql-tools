<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;

final class Reflections
{
    private const PARENT_CLASSES_MAX_DEPTH = 5;

    #[Pure]
    public static function getAllParentClasses(ReflectionClass $class): array {
        $parentClasses = [];
        $currentDepth = 0;
        $initialClassName = $class->getName();

        while ($class = $class->getParentClass()) {
            $currentDepth++;
            $parentClasses[] = $class->getName();

            if ($currentDepth > self::PARENT_CLASSES_MAX_DEPTH) {
                $maxDepth = self::PARENT_CLASSES_MAX_DEPTH;
                throw new \Exception("Reached max depth of {$maxDepth} while getting all parent classes of `{$initialClassName}`");
            }
        }

        return $parentClasses;
    }

}