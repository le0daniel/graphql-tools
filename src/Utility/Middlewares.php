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

        if (empty($callbackStack)) {
            return static fn($value) => $value;
        }

        return static function($value) use ($callbackStack): mixed {
            return array_reduce($callbackStack, static fn($carry, $callback) => $callback($carry), $value);
        };
    }

}
