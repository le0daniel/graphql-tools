<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class SideEffects
{
    public static function tap(mixed $value, callable $callback): mixed {
        $callback($value);
        return $value;
    }
}