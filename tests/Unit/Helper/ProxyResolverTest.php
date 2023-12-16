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
    private ResolveInfo $info;
    private ResolveInfo|ObjectProphecy $infoProphecy;

    protected function setUp(): void{
        $this->operationContext = new OperationContext(
            new Context(),
            new Extensions(),
        );
        $this->infoProphecy = $this->prophesize(ResolveInfo::class);
        $this->info = $this->infoProphecy->reveal();
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

    public function testFromResult() {
        $this->operationContext->cache->setResult([
            'data' => [
                'id' => 7
            ]
        ]);

        $this->info->path = ['data', 'id'];
        $resolver = new ProxyResolver(fn($d) => $d['id']);
        self::assertEquals(7, $resolver(['id' => 8], [], $this->operationContext, $this->info));
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
