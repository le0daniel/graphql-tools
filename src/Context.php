<?php

declare(strict_types=1);

namespace GraphQlTools;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Utility\Injections;

class Context
{
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

    /**
     * Every resolve function of a contextual loader can be called by the context, so
     * that you have the opportunity to inject additional services.
     *
     * You might want to inject all arguments, but this will force you to use the names
     * correctly. Therefor the solution here only injects arguments after the provided
     * positional arguments
     *
     * @param callable $aggregatedLoadingFunction
     * @param array $aggregatedData
     * @param array $arguments
     * @return mixed
     */
    public function executeAggregatedLoadingFunction(callable $aggregatedLoadingFunction, array $aggregatedData, array $arguments): mixed
    {
        return Injections::withPositionalArguments($aggregatedLoadingFunction, [$aggregatedData, $arguments, $this], $this->injectInstance(...));
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
    final public function executeMutationResolveFunction(callable $resolveFunction, mixed $data, array $arguments, ResolveInfo $info): mixed
    {
        return Injections::withPositionalArguments($resolveFunction, [$data, $arguments, $this, $info], $this->injectInstance(...));
    }

}
