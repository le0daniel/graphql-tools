<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\Utility\Resolving;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ResolvingTest extends TestCase
{
    use ProphecyTrait;

    public function testAttachProxyToField()
    {
        /** @var FieldDefinition $field */
        $field = $this->prophesize(FieldDefinition::class)->reveal();
        $field->resolveFn = fn() => null;
        $proxyResolver = new ProxyResolver();

        Resolving::attachProxyToField($field);
        self::assertInstanceOf(ProxyResolver::class, $field->resolveFn);

        $field->resolveFn = $proxyResolver;
        Resolving::attachProxyToField($field);
        self::assertSame($proxyResolver, $field->resolveFn);
    }
}
