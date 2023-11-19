<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Contract\GraphQlContext;

class Context implements GraphQlContext
{
    use HasDataloaders;

    protected function makeInstanceOfDataLoaderExecutor(string $key, array $arguments): Closure|ExecutableByDataLoader
    {
        return new $key(...$arguments);
    }
}
