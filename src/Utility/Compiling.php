<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

class Compiling
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const ENUM_VALUE_REGEX = '/^[^:]+::[^:]+$/';
    public static function absoluteClassName(string $className): string {
        return str_starts_with($className, '\\')
            ? $className
            : '\\' . $className;
    }

    private static function isPossibleClassName(string $value): bool {
        if (class_exists($value)) {
            return true;
        }

        if (preg_match(self::ENUM_VALUE_REGEX, $value)) {
            [$enumClass, $value] = explode('::', $value, 2);
            return enum_exists($enumClass);
        }

        return false;
    }

    public static function exportVariable(mixed $variable): string {
        if ($variable instanceof DateTimeInterface) {
            $className = self::absoluteClassName(DateTimeImmutable::class);
            $dateTimeFormat = self::DATE_TIME_FORMAT;
            $serializedDateTime = var_export($variable->format($dateTimeFormat), true);
            return "{$className}::createFromFormat('{$dateTimeFormat}', {$serializedDateTime})";
        }

        $serialized = var_export($variable, true);
        if (self::isPossibleClassName($serialized)) {
            return self::absoluteClassName($serialized);
        }

        return $serialized;
    }

    public static function parametersToString(ReflectionParameter ... $parameters): string {
        $parameterStrings = [];
        foreach ($parameters as $parameter) {
            $signature = "\${$parameter->getName()}";
            if ($parameter->isDefaultValueAvailable()) {
                $signature .= " = " . self::getDefaultValueOfParameter($parameter);
            }

            $parameterStrings[] = $parameter->hasType()
                ? self::reflectionTypeToString($parameter->getType()) . " {$signature}"
                : $signature;
        }

        return implode(', ', $parameterStrings);
    }

    private static function getDefaultValueOfParameter(ReflectionParameter $parameter): string {
        if ($parameter->isDefaultValueConstant()) {
            return $parameter->getDefaultValueConstantName();
        }
        return self::exportVariable($parameter->getDefaultValue());
    }

    public static function reflectionTypeToString(ReflectionNamedType|ReflectionIntersectionType|ReflectionUnionType $type) {
        if ($type instanceof ReflectionNamedType) {
            return self::namedTypeToString($type);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(self::namedTypeToString(...), $type->getTypes()));
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(self::namedTypeToString(...), $type->getTypes()));
        }

        $className = $type::class;
        throw new RuntimeException("Could not convert type ({$className}) to string.");
    }

    private static function namedTypeToString(ReflectionNamedType $type): string {
        if ($type->isBuiltin()) {
            return (string) $type;
        }

        $name = self::absoluteClassName($type->getName());
        return $type->allowsNull() ? "?{$name}" : $name;
    }

}