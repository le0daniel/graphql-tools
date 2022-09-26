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
     * @return T
     */
    public function load(mixed $item): mixed;

    /**
     * @param mixed ...$items
     * @return T
     */
    public function loadMany(mixed ...$items): SyncPromise;
}