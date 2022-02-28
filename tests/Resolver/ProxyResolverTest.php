<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Resolver;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\Context;
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
            Extensions::createFromExtensionFactories([])
        );
    }

    public function testWithExtensions(){
        $resolver = new ProxyResolver(fn() => 'Value');

        /** @var Extension $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class);
        $dummyExtension->visitField(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(fn($value) => "Extension: {$value}");

        $operationContext = new OperationContext(new Context(), new Extensions($dummyExtension->reveal()));
        $result = $resolver(null, null, $operationContext, ResolveInfoDummy::withDefaults());
        self::assertEquals('Extension: Value', $result);
    }

    public function testAsyncWithExtensions(){
        $resolver = new ProxyResolver(fn() => Deferred::create(fn() => 'Value'));

        /** @var Extension $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class);
        $dummyExtension->visitField(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(fn($value) => "Extension: {$value}");

        $operationContext = new OperationContext(new Context(), new Extensions($dummyExtension->reveal()));
        $result = $resolver(null, null, $operationContext, ResolveInfoDummy::withDefaults());
        SyncPromise::runQueue();

        self::assertEquals('Extension: Value', $result->result);
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

    public function testAttachProxyToField()
    {
        /** @var FieldDefinition $field */
        $field = $this->prophesize(FieldDefinition::class)->reveal();
        $field->resolveFn = fn() => null;
        $proxyResolver = new ProxyResolver();

        ProxyResolver::attachToField($field);
        self::assertInstanceOf(ProxyResolver::class, $field->resolveFn);

        $field->resolveFn = $proxyResolver;
        ProxyResolver::attachToField($field);
        self::assertSame($proxyResolver, $field->resolveFn);
    }
}
