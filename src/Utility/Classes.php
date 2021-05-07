<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Classes {

    public static function mightBeClassName(string $possibleClassName): bool {
        return str_contains($possibleClassName, '\\');
    }

}
