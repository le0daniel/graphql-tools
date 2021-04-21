<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;

final class Stack {

    public static function execute(array &$stack, callable $callback): void {
        array_walk(
            $stack,
            $callback
        );
    }

    public static function executeAndReturnCallback(array &$stack, callable $callback): Closure {
        $callbackStack = [];

        foreach ($stack as $resolver) {
            if ($potentialCallback = $callback($resolver)) {
                array_unshift($callbackStack, $potentialCallback);
            }
        }

        return static function(...$args) use ($callbackStack): void {
            self::execute($callbackStack, static fn($callback) => $callback(...$args));
        };
    }

}
