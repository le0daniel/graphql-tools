<?php

declare(strict_types=1);


namespace GraphQlTools\Contract;


use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;

interface DataLoader
{

    /** @return Promise|SyncPromise */
    public function loadBy(...$identifiers);

    /** @return Promise|SyncPromise */
    public function loadSingleBy($identifier);

}
