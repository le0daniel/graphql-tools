<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Registry;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Helper\Registry\FactoryTypeRegistry;
use PHPUnit\Framework\TestCase;

class FactoryTypeRegistryTest extends TestCase
{

    public function testType()
    {
        $type = Type::string();
        $registry = new FactoryTypeRegistry(
            ['typeName' => fn() => $type],
            ['alias' => 'typeName']
        );

        self::assertSame($registry->type('typeName')(), $registry->type('alias')());
    }

    public function testVerifyAliasCollisions()
    {
        $registry = new FactoryTypeRegistry(
            ['typeName' => fn() => 'name'], ['typeName' => 'otherType']
        );
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The alias `typeName` is used also as typename, which is invalid.');
        $registry->verifyAliasCollisions();
    }

    public function testNoVerifyAliasCollisions()
    {
        $registry = new FactoryTypeRegistry(
            ['typeName' => fn() => 'name'], ['typeName2' => 'otherType']
        );
        $registry->verifyAliasCollisions();
        self::assertTrue(true);
    }

    public function testEagerlyLoadType()
    {
        $type = Type::string();
        $registry = new FactoryTypeRegistry(
            ['typeName' => fn() => $type],
            ['alias' => 'typeName']
        );

        self::assertSame($registry->eagerlyLoadType('typeName'), $registry->eagerlyLoadType('alias'));
    }
}
