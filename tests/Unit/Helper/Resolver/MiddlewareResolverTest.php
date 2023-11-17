<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Resolver;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\Resolver\MiddlewareResolver;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MiddlewareResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveToValue()
    {

        $info = $this->prophesize(ResolveInfo::class)->reveal();
        $info->fieldName = 'id';

        $resolver = new MiddlewareResolver(null, [
            function ($data, $args, Context $context, ResolveInfo $info, Closure $next) {
                return $next(['id' => 123], $args, $context, $info);
            }
        ]);

        $value = $resolver->resolveToValue(null, [], new Context(), $info);
        self::assertEquals(123, $value);
    }

    public function testResolveToValueWithCustomMiddle()
    {
        $info = $this->prophesize(ResolveInfo::class)->reveal();

        $resolver = new MiddlewareResolver(fn($data) => $data['id'], [
            function ($data, $args, Context $context, ResolveInfo $info, Closure $next) {
                return $next(['id' => 1234], $args, $context, $info);
            }
        ]);

        $value = $resolver->resolveToValue(null, [], new Context(), $info);
        self::assertEquals(1234, $value);
    }
}
