<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;

final class Middlewares {

    public static function executeAndReturnNext(array &$stack, callable $callback): Closure {
        /** @var callable[] $callbackStack */
        $callbackStack = [];

        foreach ($stack as $resolver) {
            if ($potentialCallback = $callback($resolver)) {
                array_unshift($callbackStack, $potentialCallback);
            }
        }

        if (empty($callbackStack)) {
            return static fn($value) => $value;
        }

        return static function($carry) use ($callbackStack): mixed {
            foreach ($callbackStack as $next) {
                $carry = $next($carry);
            }
            return $carry;
        };
    }

}
