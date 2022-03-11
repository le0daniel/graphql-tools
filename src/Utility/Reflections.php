<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use RuntimeException;

final class Reflections
{
    private const PARENT_CLASSES_MAX_DEPTH = 5;

    public static function ofCallable(callable $callable): ReflectionFunction|ReflectionMethod {
        if (is_array($callable)) {
            return new ReflectionMethod(...$callable);
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            return new ReflectionMethod($callable);
        }

        return new ReflectionFunction($callable);
    }

    #[Pure]
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
                throw new \Exception("Reached max depth of {$maxDepth} while getting all parent classes of `{$initialClassName}`");
            }
        }

        return $parentClasses;
    }

}