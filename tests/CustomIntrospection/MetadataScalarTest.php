<?php declare(strict_types=1);

namespace GraphQlTools\Test\CustomIntrospection;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQlTools\CustomIntrospection\MetadataScalar;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;
use Throwable;

class MetadataScalarTest extends TestCase
{
    use ProphecyTrait;

    public function testSerialize()
    {
        self::assertEquals('string', (new MetadataScalar)->serialize('string'));
        self::assertEquals(['test'], (new MetadataScalar)->serialize(['test']));
        self::assertEquals(1.1, (new MetadataScalar)->serialize(1.1));
        self::assertEquals(100, (new MetadataScalar)->serialize(100));
    }

    public function testParseValue()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This is only a return type and can not be parsed');
        (new MetadataScalar())->parseValue('');
    }

    public function testParseLiteral()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This is only a return type and can not be parsed');
        (new MetadataScalar())->parseLiteral($this->prophesize(Node::class)->reveal());
    }
}
