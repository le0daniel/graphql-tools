<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

class Injections
{
    public static function withPositionalArguments(callable $callable, array $positionalArguments, callable $createInstanceOfClass)
    {
        $reflection = Reflections::ofCallable($callable);
        $arguments = Lists::mapWithIndex($reflection->getParameters(), static function (int $index, ReflectionParameter $parameter) use ($positionalArguments, $createInstanceOfClass) {
            if (isset($positionalArguments[$index])) {
                return $positionalArguments[$index];
            }

            $type = $parameter->getType();
            $isInjectable = $parameter->hasType() && $type instanceof ReflectionNamedType && !$type->isBuiltin();
            $defaultParameter = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

            if ($isInjectable) {
                return $createInstanceOfClass($type->getName()) ?? $defaultParameter;
            }

            if ($parameter->isDefaultValueAvailable() || $parameter->allowsNull()) {
                return $defaultParameter;
            }

            $typeName = $type?->getName() ?? 'no type given';
            throw new RuntimeException("Cannot inject argument with name '{$parameter->name}' as the type '{$typeName}' is not supported.");
        });

        return $callable(...$arguments);
    }

}