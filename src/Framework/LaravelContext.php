<?php declare(strict_types=1);

namespace GraphQlTools\Framework;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Utility\Injections;
use Psr\Container\ContainerInterface;

class LaravelContext extends Context
{

    public function __construct(private ContainerInterface $container)
    {
    }

    public function executeAggregatedLoadingFunction(callable $resolveFunction, array $aggregatedData, array $arguments): mixed
    {
        return Injections::withPositionalArguments(
            $resolveFunction,
            [$aggregatedData, $arguments, $this],
            $this->container->get(...)
        );
    }

    public function executeMutationResolveFunction(callable $resolveFunction, mixed $data, array $arguments, ResolveInfo $info): mixed
    {
        return Injections::withPositionalArguments(
            $resolveFunction,
            [$data, $arguments, $this, $info],
            $this->container->get(...)
        );
    }

}