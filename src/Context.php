<?php

declare(strict_types=1);

namespace GraphQlTools;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Utility\Injections;

class Context
{

    /**
     * Every resolve function of a contextual loader can be called by the context, so
     * that you have the opportunity to inject additional services.
     *
     * @param callable $resolveFunction
     * @param array $aggregatedData
     * @param array $arguments
     * @return mixed
     */
    public function executeAggregatedLoadingFunction(callable $resolveFunction, array $aggregatedData, array $arguments): mixed
    {
        return Injections::withPositionalArguments($resolveFunction, [$aggregatedData, $arguments, $this], fn() => null);
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
        return Injections::withPositionalArguments($resolveFunction, [$data, $arguments, $this, $info], fn() => null);
    }

}
