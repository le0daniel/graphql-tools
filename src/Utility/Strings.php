<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Strings {

    public static function baseClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    public static function pathToString(array $path): string {
        $parts = array_map(fn($part) => is_string($part) ? $part : '[]', $path);
        return implode('.', $parts);
    }

}
