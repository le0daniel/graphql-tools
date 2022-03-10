<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;

class Injections
{

    public static function withPositionalArguments(callable $callable, array $positionalArguments, callable $createInstanceOfClass){
        $reflection = new ReflectionFunction($callable);
        $arguments = [];

        foreach ($reflection->getParameters() as $index => $parameter) {
            if (isset($positionalArguments[$index])) {
                $arguments[] = $positionalArguments[$index];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if (!$parameter->hasType()) {
                throw new RuntimeException("Cannot inject argument with name '{$parameter->name}' as there is no type defined.");
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType) {
                $className = get_class($type);
                throw new RuntimeException("Cannot inject argument with name '{$parameter->name}' as the type is '{$className}'");
            }

            if ($type->isBuiltin()) {
                throw new RuntimeException("Cannot inject argument with name '{$parameter->name}' as it is a builtin type.'");
            }

            $arguments[] = $createInstanceOfClass($type->getName());
        }

        return $callable(...$arguments);
    }

}