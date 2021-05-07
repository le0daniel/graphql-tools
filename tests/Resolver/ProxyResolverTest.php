<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Resolver;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Execution\OperationContext;
use GraphQlTools\Execution\ExtensionManager;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\Context;
use GraphQlTools\Resolver\ProxyResolver;
use PHPUnit\Framework\TestCase;

final class ProxyResolverTest extends TestCase {

    private OperationContext $operationContext;

    protected function setUp(): void{
        $this->operationContext = new OperationContext(
            new Context(),
            ExtensionManager::create([])
        );
    }

    /**
     * @throws \Throwable
     */
    public function testInvocation(): void {
        $resolver = new ProxyResolver(fn(array $typeData) => $typeData['username']);
        $result = $resolver(
            ['username' => 'Test'],
            null,
            $this->operationContext,
            ResolveInfoDummy::withDefaults()
        );
        self::assertEquals('Test', $result);
        self::assertCount(0, $this->operationContext->context->getUsedLoaders());
    }

    /**
     * @throws \Throwable
     */
    public function testInvocationWithMessages(): void {
        $resolver = new ProxyResolver(fn(array $typeData) => $typeData['username']);
        $result = $resolver(
            ['username' => 'Test'],
            null,
            $this->operationContext,
            ResolveInfoDummy::withDefaults('test')
        );
        self::assertEquals('Test', $result);
        self::assertCount(0, $this->operationContext->context->getUsedLoaders());
    }

    /**
     * @throws \Throwable
     */
    public function testInvocationWithPromise(): void {
        $resolver = new ProxyResolver(fn(array $typeData) => new Deferred(fn() => $typeData['username']));
        $promise = $resolver(
            ['username' => 'Test'],
            null,
            $this->operationContext,
            ResolveInfoDummy::withDefaults()
        );

        SyncPromise::runQueue();
        self::assertEquals('Test', $promise->result);
        self::assertCount(0, $this->operationContext->context->getUsedLoaders());
    }
}
