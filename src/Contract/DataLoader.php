<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * @template T
 */
interface DataLoader
{

    /**
     * @param mixed $item
     * @return T|SyncPromise
     */
    public function load(mixed $item): mixed;

    /**
     * @param mixed ...$items
     * @return T|SyncPromise
     */
    public function loadMany(mixed ...$items): mixed;
}