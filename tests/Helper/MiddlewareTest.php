<?php declare(strict_types=1);

namespace GraphQlTools\Test\Helper;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Middleware;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class MiddlewareTest extends TestCase
{
    use ProphecyTrait;

    protected ObjectProphecy|GraphQlContext $context;
    protected ObjectProphecy|ResolveInfo $resolveInfo;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(GraphQlContext::class);
        $this->resolveInfo = $this->prophesize(ResolveInfo::class);
    }

    public function testMiddleware(): void {
        $pipeLine = Middleware::create([
            function($data, array $arguments, GraphQlContext $context, ResolveInfo $info, Closure $next): string {
                return 'First: ' . $next($data, $arguments, $context, $info);
            },
            function($data, array $arguments, GraphQlContext $context, ResolveInfo $info, Closure $next): string {
                return 'Second: ' . $next($data, $arguments, $context, $info);
            }
        ]);

        $executor = $pipeLine->then(fn(string $middle): string => 'data = ' . $middle);

        self::assertEquals('First: Second: data = middle', $executor('middle', [], $this->context->reveal(), $this->resolveInfo->reveal()));
    }

    public function testBlockingMiddleware(): void {
        $pipeLine = Middleware::create([
            function(): string {
                return 'First Blocked';
            },
            function($data, array $arguments, GraphQlContext $context, ResolveInfo $info, Closure $next): string {
                return 'Second: ' . $next($data, $arguments, $context, $info);
            }
        ]);

        $executor = $pipeLine->then(fn(string $middle): string => 'data = ' . $middle);

        self::assertEquals('First Blocked', $executor('middle', [], $this->context->reveal(), $this->resolveInfo->reveal()));
    }

    public function testBlocking2Middleware(): void {
        $pipeLine = Middleware::create([
            function($data, array $arguments, GraphQlContext $context, ResolveInfo $info, Closure $next): string {
                return 'First: ' . $next($data, $arguments, $context, $info);
            },
            function(): string {
                return 'Second Blocked';
            },
        ]);

        $executor = $pipeLine->then(fn(string $middle): string => 'data = ' . $middle);

        self::assertEquals('First: Second Blocked', $executor('middle', [], $this->context->reveal(), $this->resolveInfo->reveal()));
    }
}