<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use ReflectionNamedType;
use RuntimeException;

class Injections
{

    public static function withPositionalArguments(callable $callable, array $positionalArguments, callable $createInstanceOfClass){
        $reflection = Reflections::ofCallable($callable);
        $arguments = [];

        foreach ($reflection->getParameters() as $index => $parameter) {
            if (isset($positionalArguments[$index])) {
                $arguments[] = $positionalArguments[$index];
                continue;
            }

            $type = $parameter->getType();
            $isInjectable = $parameter->hasType() && $type instanceof ReflectionNamedType && !$type->isBuiltin();
            $defaultParameter = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;

            if ($isInjectable) {
                $arguments[] = $createInstanceOfClass($type->getName()) ?? $defaultParameter;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $defaultParameter;
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            $typeName = $type?->getName() ?? 'no type given';
            throw new RuntimeException("Cannot inject argument with name '{$parameter->name}' as the type '{$typeName}' is not supported.");
        }

        return $callable(...$arguments);
    }

}