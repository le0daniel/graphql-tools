<?php declare(strict_types=1);

namespace GraphQlTools\Framework;

use Closure;
use GraphQlTools\Context;
use GraphQlTools\Contract\ExecutableByDataLoader;
use Psr\Container\ContainerInterface;

class LaravelContext extends Context
{

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure|ExecutableByDataLoader
    {
        return $this->container->get($classNameOrLoaderName);
    }
}