<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

final class Promises
{
    private static string $promiseClassName = SyncPromise::class;

    /**
     * @api
     * @param string $className
     * @return void
     */
    public static function setPromiseAdapterClass(string $className): void {
        self::$promiseClassName = $className;
    }

    /**
     * @template T
     * @param T $potentialPromise
     * @phpstan-assert-if-true SyncPromise $object
     */
    public static function isPromise(mixed $potentialPromise): bool {
        return $potentialPromise instanceof self::$promiseClassName;
    }

}