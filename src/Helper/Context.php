<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Helper\DataLoader;
use GraphQlTools\Contract\ExecutableByDataLoader;

class Context
{
    private array $dataLoaders = [];

    /**
     * Create an instance of a DataLoader executor, Must either be a callable or
     * an instance extending the ExecutableByDataLoader contract.
     *
     * @param string $classNameOrLoaderName
     * @return Closure|ExecutableByDataLoader
     */
    protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure|ExecutableByDataLoader {
        return new $classNameOrLoaderName;
    }

    final public function withDataLoader(string $classNameOrLoaderName): DataLoader {
        if (!isset($this->dataLoaders[$classNameOrLoaderName])) {
            $this->dataLoaders[$classNameOrLoaderName] = new DataLoader(
                $this->makeInstanceOfDataLoaderExecutor($classNameOrLoaderName)
            );
        }

        return $this->dataLoaders[$classNameOrLoaderName];
    }
}
