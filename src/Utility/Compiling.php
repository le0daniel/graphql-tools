<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQlTools\Data\ValueObjects\RawPhpExpression;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

class Compiling
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const ENUM_VALUE_REGEX = '/^[^:]+::[^:]+$/';

    public static function absoluteClassName(string $className): string
    {
        return str_starts_with($className, '\\')
            ? $className
            : '\\' . $className;
    }

    private static function isClassName(string $value): bool
    {
        return class_exists($value);
    }

    private static function isEnum(string $value): bool
    {
        if (preg_match(self::ENUM_VALUE_REGEX, $value)) {
            [$enumClass, $value] = explode('::', $value, 2);
            return enum_exists($enumClass);
        }
        return false;
    }

    public static function exportArray(array $values): string
    {
        $exported = [];
        $arrayIsList = array_is_list($values);

        foreach ($values as $key => $value) {
            $exportedKey = self::exportVariable($key);
            $exportedValue = is_array($value)
                ? self::exportArray($value)
                : self::exportVariable($value);
            $exported[] = $arrayIsList
                ? $exportedValue
                : "{$exportedKey} => {$exportedValue}";
        }

        return '[' . implode(',', $exported) . ']';
    }

    public static function exportVariable(mixed $variable): string
    {
        if ($variable instanceof RawPhpExpression) {
            return $variable->toString();
        }

        if ($variable instanceof DateTimeInterface) {
            $className = self::absoluteClassName($variable::class);
            $dateTimeFormat = self::DATE_TIME_FORMAT;
            $serializedDateTime = var_export($variable->format($dateTimeFormat), true);
            return "{$className}::createFromFormat('{$dateTimeFormat}', {$serializedDateTime})";
        }

        $serialized = var_export($variable, true);
        if (self::isClassName($serialized) || self::isEnum($serialized)) {
            return self::absoluteClassName($serialized);
        }

        return $serialized;
    }

    public static function parametersToString(ReflectionParameter ...$parameters): string
    {
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

    private static function getDefaultValueOfParameter(ReflectionParameter $parameter): string
    {
        if (!$parameter->isDefaultValueConstant()) {
            return self::exportVariable($parameter->getDefaultValue());
        }

        $constName = $parameter->getDefaultValueConstantName();
        if (!str_contains($constName, '::')) {
            return $constName;
        }

        if (self::isEnum($constName)) {
            return self::absoluteClassName($constName);
        }

        [$scope, $constName] = explode('::', $constName);
        if ($scope === 'self') {
            $className = self::absoluteClassName($parameter->getDeclaringClass()->getName());
            return "{$className}::{$constName}";
        }

        return self::absoluteClassName($scope) . "::{$constName}";
    }

    public static function reflectionTypeToString(ReflectionNamedType|ReflectionIntersectionType|ReflectionUnionType $type)
    {
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

    private static function namedTypeToString(ReflectionNamedType $type): string
    {
        if ($type->isBuiltin()) {
            return (string)$type;
        }

        $name = self::absoluteClassName($type->getName());
        return $type->allowsNull() ? "?{$name}" : $name;
    }

}