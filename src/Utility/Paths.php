<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Paths {

    public static function toString(array $path): string {
        $parts = array_map(fn($part) => is_string($part) ? $part : '[]', $path);
        return implode('.', $parts);
    }

}
