<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Paths {

    public const DEFAULT_SEPARATOR = '[]';

    public static function toString(array $path, string $arraySeparator = self::DEFAULT_SEPARATOR): string {
        $parts = array_map(fn($part) => is_string($part) ? $part : $arraySeparator, $path);
        return implode('.', $parts);
    }

}
