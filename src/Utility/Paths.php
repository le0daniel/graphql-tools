<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Paths
{

    public static function toString(array $path): string
    {
        return implode('.', $path);
    }

    public static function toNormalizedString(array $path): string
    {
        return implode('.', array_map(fn(string|int $path): string => is_int($path) ? '[]' : $path, $path));
    }

}