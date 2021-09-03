<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class SideEffects
{

    /**
     * Executes a callback by passing the value to it. Then returns the value regardless of what the
     * Callback returned.
     *
     * @param mixed $value
     * @param callable $callback
     * @return mixed
     */
    public static function tap(mixed $value, callable $callback): mixed {
        $callback($value);
        return $value;
    }
}