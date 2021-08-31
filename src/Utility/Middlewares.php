<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;

final class Middlewares {

    public static function executeAndReturnNext(array &$stack, callable $callback): Closure {
        $callbackStack = [];

        foreach ($stack as $resolver) {
            if ($potentialCallback = $callback($resolver)) {
                array_unshift($callbackStack, $potentialCallback);
            }
        }

        return static function() use ($callbackStack): void {
            array_walk($callbackStack, static fn($callback) => $callback(...func_get_args()));
        };
    }

}
