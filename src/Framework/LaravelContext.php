<?php declare(strict_types=1);

namespace GraphQlTools\Framework;

use GraphQlTools\Context;
use Psr\Container\ContainerInterface;

class LaravelContext extends Context
{

    public function __construct(private ContainerInterface $container)
    {
    }

    protected function injectInstance(string $className): mixed
    {
        return $this->container->get($className);
    }

}