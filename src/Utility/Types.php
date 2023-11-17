<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Definition\DefinitionException;

final class Types
{
    public const TYPE_NAME_ENDING = 'Type';
    public const ENUM_NAME_ENDING = 'Enum';
    public const INTERFACE_NAME_ENDING = 'Interface';
    public const SCALAR_NAME_ENDING = 'Scalar';
    public const UNION_NAME_ENDING = 'Union';
    public const DIRECTIVE_NAME_ENDING = 'Directive';

    public static function inferNameFromClassName(string $className): string
    {
        $parts = explode('\\', $className);
        $baseName = end($parts);

        return match (true) {
            str_ends_with($baseName, self::TYPE_NAME_ENDING), str_ends_with($baseName, self::ENUM_NAME_ENDING) => substr($baseName, 0, -4),
            str_ends_with($baseName, self::INTERFACE_NAME_ENDING) => substr($baseName, 0, -9),
            str_ends_with($baseName, self::SCALAR_NAME_ENDING) => substr($baseName, 0, -6),
            str_ends_with($baseName, self::UNION_NAME_ENDING) => substr($baseName, 0, -5),
            str_ends_with($baseName, self::DIRECTIVE_NAME_ENDING) => lcfirst(substr($baseName, 0, -9)),
            self::isTypeExtension($baseName) => self::inferExtensionName($className),
            default => throw new DefinitionException("Could not infer name from class name string."),
        };
    }

    public static function isTypeExtension(string $className): bool
    {
        $parts = explode('\\', $className);
        $baseName = end($parts);

        return str_starts_with($baseName, 'Extends') && (
                str_ends_with($baseName, 'Type') || str_ends_with($baseName, 'Interface')
            );
    }

    public static function inferExtensionName(string $className): string
    {
        $parts = explode('\\', $className);
        $baseName = end($parts);

        if (str_starts_with($baseName, 'Extends')) {
            $baseName = substr($baseName, strlen('Extends'));
        }

        return match (true) {
            str_ends_with($baseName, 'Type') => substr($baseName, 0, -4),
            str_ends_with($baseName, 'Interface') => substr($baseName, 0, -9),
            default => $baseName
        };
    }

    public static function isDirective(string $className): bool
    {
        return str_ends_with($className, self::DIRECTIVE_NAME_ENDING);
    }
}