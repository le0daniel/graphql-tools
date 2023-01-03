<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use DateTimeImmutable;
use DateTimeInterface;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

class Compiling
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public static function absoluteClassName(string $className): string {
        return str_starts_with($className, '\\')
            ? $className
            : '\\' . $className;
    }

    private static function isPossibleClassName(string $value): bool {
        return class_exists($value);
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

    public static function parameterTypeToString(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type): string {
        if ($type->isBuiltin()) {
            return $type->allowsNull() && $type->getName() !== 'mixed'
                ? "?{$type->getName()}"
                : $type->getName();
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->allowsNull()
                ? '?' . self::absoluteClassName($type->getName())
                : self::absoluteClassName($type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('&', array_map(self::parameterTypeToString(...), $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('|', array_map(self::parameterTypeToString(...), $type->getTypes()));
        }

        throw new RuntimeException("Invalid type given.");
    }

}