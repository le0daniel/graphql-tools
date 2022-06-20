<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

class Promises
{

    final public static function is(mixed $potentialPromise): bool
    {
        return is_object($potentialPromise) && method_exists($potentialPromise, 'then') && method_exists($potentialPromise, 'catch');
    }

}