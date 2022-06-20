<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use ArrayAccess;

interface ExecutableByDataLoader
{

    /**
     * Fetch queued items with optional arguments
     *
     * @param array $queuedItems
     * @return array|ArrayAccess
     */
    public function fetchData(array $queuedItems): array|ArrayAccess;

}