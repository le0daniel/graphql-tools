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

    private static function fromCallable(callable $callable): ReflectionFunction|ReflectionMethod
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            return str_contains($callable, '::')
                ? new ReflectionMethod($callable)
                : new ReflectionFunction($callable);
        }

        if (is_array($callable)) {
            [$object, $method] = $callable;
            return new ReflectionMethod($object, $method);
        }

        if (is_object($callable)) {
            return (new ReflectionObject($callable))->getMethod('__invoke');
        }

        $type = gettype($callable);
        throw new RuntimeException("Could not resolve callable to reflection for value: '{$type}'");
    }

    public static function getAttributesOfCallable(callable $callable): array
    {
        $instances = [];
        foreach (self::fromCallable($callable)->getAttributes() as $attribute) {
            $instances[] = $attribute->newInstance();
        }
        return $instances;
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