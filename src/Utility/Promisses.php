<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

final class Promisses
{
    private static string $promiseClassName = SyncPromise::class;

    public static function setPromiseClass(string $className): void {
        self::$promiseClassName = $className;
    }

    public static function isPromise(mixed $potentialPromise): bool {
        return $potentialPromise instanceof self::$promiseClassName;
    }

}