<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface ExecutableByDataLoader
{

    /**
     * Fetch queued items with optional arguments
     *
     * @param array $queuedItems
     * @param array $arguments
     * @return mixed
     */
    public function fetchData(array $queuedItems, array $arguments): mixed;

}