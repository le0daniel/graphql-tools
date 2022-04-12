<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface ExecutableByDataLoader
{

    public function fetchData(array $queuedItems, array $arguments): mixed;

}