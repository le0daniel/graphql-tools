<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use ArrayAccess;

/**
 * @template T
 */
interface ExecutableByDataLoader
{

    /**
     * Fetch queued items with optional arguments
     *
     * @template R
     * @param array<T> $queuedItems
     * @return array<R>|ArrayAccess<R>
     */
    public function fetchData(array $queuedItems): array|ArrayAccess;

}