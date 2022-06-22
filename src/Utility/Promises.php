<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

class Promises
{

    final public static function is(mixed $potentialPromise): bool
    {
        return $potentialPromise instanceof SyncPromise;
    }

}