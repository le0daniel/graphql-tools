<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Strings {

    public static function baseClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

}
