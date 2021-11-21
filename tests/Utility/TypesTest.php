<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Utility\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{

    public function testEnforceTypeLoading()
    {
        self::assertInstanceOf(Type::class, Types::enforceTypeLoading(fn() => Type::int()));
        self::assertInstanceOf(Type::class, Types::enforceTypeLoading(Type::int()));
    }
}
