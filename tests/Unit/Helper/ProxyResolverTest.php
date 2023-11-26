<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use Closure;
use Exception;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\Extension\Extension;
use GraphQlTools\Helper\Extensions;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Helper\Resolver\ProxyResolver;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

final class ProxyResolverTest extends TestCase {
    use ProphecyTrait;

    private OperationContext $operationContext;

    protected function setUp(): void{
        $this->operationContext = new OperationContext(
            new Context(),
            Extensions::createFromExtensionFactories($this->prophesize(GraphQlContext::class)->reveal(),[])
        );
    }

    /**
     * @return array{0: OperationContext|ObjectProphecy, 1: ResolveInfo|ObjectProphecy}
     */
    private function getProphecies(): array {
        return [
             $this->prophesize(OperationContext::class),
             $this->prophesize(ResolveInfo::class),
        ];
    }

    public function testOnCacheHit() {
        $resolver = new ProxyResolver(fn($d) => throw new Exception());

        [$context, $resolveInfo] = $this->getProphecies();
        $resolveInfo->path = ['query', 1];

        $context->isInResult(['query', 1])->willReturn(true);
        $context->getFromResult(['query', 1])->willReturn('result');

        self::assertEquals('result', $resolver([], [], $context->reveal(), $resolveInfo->reveal()));
    }

    public function testHasBeenDeferred() {
        $resolver = new ProxyResolver(fn($d) => $d['id']);

        [$context, $resolveInfo] = $this->getProphecies();
        $resolveInfo->path = ['query', 1];

        $context->isInResult(['query', 1])->willReturn(false);
        $context->isDeferred(['query', 1])->willReturn(true);
        $context->popDeferred(['query', 1])->willReturn(['id' => 7]);
        $context->getContext()->willReturn($this->prophesize(GraphQlContext::class)->reveal());
        $context->willResolveField(Argument::type(VisitFieldEvent::class))->shouldBeCalledOnce();

        self::assertEquals(7, $resolver([], [], $context->reveal(), $resolveInfo->reveal()));
    }

    public function testMarkedAsDeferred() {
        $resolver = new ProxyResolver(fn($d) => $d['id']);

        [$context, $resolveInfo] = $this->getProphecies();
        $resolveInfo->path = ['query', 1];

        $context->isInResult(['query', 1])->willReturn(false);
        $context->isDeferred(['query', 1])->willReturn(false);

        $context->getContext()->willReturn($this->prophesize(GraphQlContext::class)->reveal());
        $context->deferField(['query', 1], null, [])->shouldBeCalledOnce();

        $context->willResolveField(Argument::type(VisitFieldEvent::class))
            ->shouldBeCalledOnce()
            ->will(function($args) {$args[0]->defer();});

        self::assertEquals(null, $resolver([], [], $context->reveal(), $resolveInfo->reveal()));
    }

    public function testMarkedAsDeferredOnlyWorksOnce() {
        $resolver = new ProxyResolver(fn($d) => $d['id']);

        [$context, $resolveInfo] = $this->getProphecies();
        $resolveInfo->path = ['query', 1];

        $context->isInResult(['query', 1])->willReturn(false);
        $context->isDeferred(['query', 1])->willReturn(true);
        $context->popDeferred(['query', 1])->willReturn(['id' => 7]);

        $context->getContext()->willReturn($this->prophesize(GraphQlContext::class)->reveal());
        $context->deferField(['query', 1], null, [])->shouldNotBeCalled();

        $context->willResolveField(Argument::type(VisitFieldEvent::class))
            ->shouldBeCalledOnce()
            ->will(function($args) {$args[0]->defer();});

        self::assertEquals(7, $resolver(['id' => 8], [], $context->reveal(), $resolveInfo->reveal()));
    }

    public function testWithExtensions(){
        $resolver = new ProxyResolver(fn() => 'Value');

        /** @var Extension|ObjectProphecy $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class)->willImplement(InteractsWithFieldResolution::class);
        $dummyExtension->getName()->willReturn('dummy');

        $operationContext = new OperationContext(new Context(), new Extensions($dummyExtension->reveal()));
        $resolveInfo = ResolveInfoDummy::withDefaults();

        $dummyExtension->visitField(Argument::type(VisitFieldEvent::class))
            ->will(function() {});

        $result = $resolver(null, null, $operationContext, $resolveInfo);
        self::assertEquals('Value', $result);
    }

    public function testAsyncWithExtensions(){
        $resolver = new ProxyResolver(fn() => Deferred::create(fn() => 'Value'));

        /** @var Extension $dummyExtension */
        $dummyExtension = $this->prophesize(Extension::class)->willImplement(InteractsWithFieldResolution::class);
        $dummyExtension->getName()->willReturn('here it is');
        $dummyExtension->visitField(Argument::type(VisitFieldEvent::class))
            ->will(function() {});;

        $operationContext = new OperationContext(new Context(), new Extensions($dummyExtension->reveal()));
        $result = $resolver(null, null, $operationContext, ResolveInfoDummy::withDefaults());
        SyncPromise::runQueue();

        self::assertEquals('Value', $result->result);
    }

    /**
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Throwable
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

    public function testResolveToValue()
    {

        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'id';
        $info->path = ['query'];

        $resolver = new ProxyResolver(null, [
            function ($data, $args, Context $context, ResolveInfo $info, Closure $next) {
                return $next(['id' => 123], $args, $context, $info);
            }
        ]);

        $operationContext = new OperationContext(new Context(), new Extensions());

        $value = $resolver(null, [], $operationContext, $info);
        self::assertEquals(123, $value);
    }

    public function testResolveToValueWithCustomMiddle()
    {
        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->path = ['field'];

        $resolver = new ProxyResolver(fn($data) => $data['id'], [
            function ($data, $args, Context $context, ResolveInfo $info, Closure $next) {
                return $next(['id' => 1234], $args, $context, $info);
            }
        ]);

        $operationContext = new OperationContext(new Context(), new Extensions());

        $value = $resolver(null, [], $operationContext, $info);
        self::assertEquals(1234, $value);
    }
}
