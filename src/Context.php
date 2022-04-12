<?php

declare(strict_types=1);

namespace GraphQlTools;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Utility\Injections;
use GraphQlTools\Utility\Paths;

class Context
{
    private array $dataLoaders = [];

    /**
     * Return an instance of a class given it's type name. This is very useful for
     * service injection on Aggregated Loading Functions or Mutation fields.
     *
     * @param string $className
     * @return mixed
     */
    protected function injectInstance(string $className): mixed {
        return null;
    }

    protected function makeInstanceOfDataLoaderExecutor(string $className): ExecutableByDataLoader {
        return new $className;
    }

    private function computeDataLoaderKey(string $executorClassName, array $arguments, ResolveInfo $resolveInfo): string {
        $path = Paths::toString($resolveInfo->path);
        $encodedArguments = json_encode($arguments, JSON_THROW_ON_ERROR);
        return "{$executorClassName}:{$path}:{$encodedArguments}";
    }

    final public function withDataLoader(string $executorClassName, array $arguments, ResolveInfo $resolveInfo): DataLoader {
        $key = $this->computeDataLoaderKey($executorClassName, $arguments, $resolveInfo);
        if (!isset($this->dataLoaders[$key])) {
            $this->dataLoaders[$key] = new DataLoader($this->makeInstanceOfDataLoaderExecutor($executorClassName), $arguments);
        }

        return $this->dataLoaders[$key];
    }

    /**
     * Mutation fields can be called by the
     *
     * @param callable $resolveFunction
     * @param mixed $data
     * @param array $arguments
     * @param ResolveInfo $info
     * @return mixed
     */
    public function executeMutationResolveFunction(callable $resolveFunction, mixed $data, array $arguments, ResolveInfo $info): mixed
    {
        return Injections::withPositionalArguments($resolveFunction, [$data, $arguments, $this, $info], $this->injectInstance(...));
    }

}
