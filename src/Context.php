<?php

declare(strict_types=1);

namespace GraphQlTools;

class Context
{

    /**
     * Every resolve function of a contextual loader can be called by the context, so
     * that you have the opportunity to inject additional services.
     *
     * @param callable $resolveFunction
     * @param array $positionalArguments = [array $aggregatedData, array $arguments]
     * @return mixed
     */
    public function executeResolveFunction(callable $resolveFunction, array $positionalArguments): mixed
    {
        [$aggregatedData, $arguments] = $positionalArguments;
        return $resolveFunction($aggregatedData, $arguments, $this);
    }

    /**
     * Mutation fields can be called by the
     *
     * @param callable $resolveFunction
     * @param array $positionalArguments
     * @return void
     */
    public function executeMutationResolveFunction(callable $resolveFunction, array $positionalArguments): mixed
    {
        [$data, $arguments, $info] = $positionalArguments;
        return $resolveFunction($data, $arguments, $this, $info);
    }

}
