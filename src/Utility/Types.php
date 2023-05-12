<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Definition\DefinitionException;

final class Types
{
    public static function inferNameFromClassName(string $className): string
    {
        $baseName = Classes::baseName($className);
        return match (true) {
            str_ends_with($baseName, 'Type'), str_ends_with($baseName, 'Enum') => substr($baseName, 0, -4),
            str_ends_with($baseName, 'Interface') => substr($baseName, 0, -9),
            str_ends_with($baseName, 'Scalar') => substr($baseName, 0, -6),
            str_ends_with($baseName, 'Union') => substr($baseName, 0, -5),
            default => throw new DefinitionException("Could not infer name from class name string."),
        };
    }
}