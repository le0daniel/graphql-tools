<?php declare(strict_types=1);

namespace GraphQlTools\Framework;

use GraphQlTools\Context;
use GraphQlTools\Contract\ExecutableByDataLoader;
use Psr\Container\ContainerInterface;

class LaravelContext extends Context
{

    public function __construct(private ContainerInterface $container)
    {
    }

    protected function makeInstanceOfDataLoaderExecutor(string $className): callable|ExecutableByDataLoader
    {
        return $this->container->get($className);
    }

    protected function injectInstance(string $className): mixed
    {
        return $this->container->get($className);
    }

}