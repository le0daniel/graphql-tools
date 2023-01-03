<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Helper\ExtensionManager;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\ProxyResolver;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class ProxyResolverTest extends TestCase {
    use ProphecyTrait;

    private OperationContext $operationContext;

    protected function setUp(): void{
        $this->operationContext = new OperationContext(
            new Context(),
            ExtensionManager::createFromExtensionFactories([])
        );
    }

    public function testWithExtensions(){
        $resolver = new ProxyResolver(fn() => 'Value');

        /** @var Extension|ObjectProphecy $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class);
        $operationContext = new OperationContext(new Context(), new ExtensionManager($dummyExtension->reveal()));
        $resolveInfo = ResolveInfoDummy::withDefaults();

        $dummyExtension->visitField(Argument::type(VisitFieldEvent::class))
            ->willReturn(fn($value) => "Extension: {$value}");

        $result = $resolver(null, null, $operationContext, $resolveInfo);
        self::assertEquals('Value', $result);
    }

    public function testAsyncWithExtensions(){
        $resolver = new ProxyResolver(fn() => Deferred::create(fn() => 'Value'));

        /** @var Extension $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class);
        $dummyExtension->visitField(Argument::type(VisitFieldEvent::class))
            ->willReturn(fn($value) => "Extension: {$value}");

        $operationContext = new OperationContext(new Context(), new ExtensionManager($dummyExtension->reveal()));
        $result = $resolver(null, null, $operationContext, ResolveInfoDummy::withDefaults());
        SyncPromise::runQueue();

        self::assertEquals('Value', $result->result);
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
    }

    /**
     * @throws \Throwable
     */
    public function testInvocationWithPromise(): void {
        $resolver = new ProxyResolver(fn(array $typeData) => Deferred::create(fn() => $typeData['username']));
        $promise = $resolver(
            ['username' => 'Test'],
            null,
            $this->operationContext,
            ResolveInfoDummy::withDefaults()
        );

        SyncPromise::runQueue();
        self::assertEquals('Test', $promise->result);
    }
}
