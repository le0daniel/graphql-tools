<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlDirective;

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
            default => throw new DefinitionException("Could not infer name from class name string."),
        };
    }

    public static function inferExtensionTypeName(string $className): string
    {
        $parts = explode('\\', $className);
        $baseName = end($parts);

        if (!str_starts_with($baseName, 'Extends')) {
            throw new DefinitionException("Could not infer type name from string: {$baseName}. Expected string to start with 'Extends'.");
        }

        $typeNameWithoutExtendsKeyword = substr($baseName, strlen('Extends'));

        return match (true) {
            str_ends_with($typeNameWithoutExtendsKeyword, 'Type') => substr($typeNameWithoutExtendsKeyword, 0, -4),
            str_ends_with($typeNameWithoutExtendsKeyword, 'Interface') => substr($typeNameWithoutExtendsKeyword, 0, -9),
            default => throw new DefinitionException("Could not infer type name from string: {$baseName}. Expected string to end in 'Type' or 'Interface'."),
        };
    }

    public static function isDirective(string|DefinesGraphQlType $classNameOrInstance): bool
    {
        return is_string($classNameOrInstance)
            ? str_ends_with($classNameOrInstance, self::DIRECTIVE_NAME_ENDING)
            : $classNameOrInstance instanceof GraphQlDirective;
    }
}