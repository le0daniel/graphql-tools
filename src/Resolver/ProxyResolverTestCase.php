<?php

declare(strict_types=1);

namespace GraphQlTools\Resolver;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Execution\ExtensionManager;
use GraphQlTools\Execution\OperationContext;
use PHPUnit\Framework\TestCase;

abstract class ProxyResolverTestCase extends TestCase {

    private ProxyResolver $resolver;
    private ResolveInfo $resolveInfo;

    /**
     * Create an instance of your own Proxy Resolver.
     *
     * @return ProxyResolver
     */
    abstract protected function createProxyResolverInstance(): ProxyResolver;

    protected function setUp(): void {
        $this->resolver = $this->createProxyResolverInstance();

        /** @var ResolveInfo $resolveInfo */
        $resolveInfo = (new \ReflectionClass(ResolveInfo::class))->newInstanceWithoutConstructor();
        $this->resolveInfo = $resolveInfo;
    }

    /**
     * @throws \Throwable
     */
    protected function resolveWith(mixed $typeData, array $arguments = [], ?Context $context = null): mixed {
        $operationContext = new OperationContext(
            $context ?? new Context(),
            new ExtensionManager
        );

        $value = ($this->resolver)(
            $typeData, $arguments, $operationContext, $this->resolveInfo
        );

        if ($value instanceof SyncPromise) {
            SyncPromise::runQueue();
            return $value->result;
        }

        return $value;
    }

}
