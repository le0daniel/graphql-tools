<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Test\Dummies\Schema\UserType;

class QueryTest extends ExecutionTestCase
{

    protected function typeRepository(): TypeRegistry
    {
        $registry = new TypeRegistry(
            TypeRegistry::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema')
        );

        $registry->extend(
            UserType::class,
            Field::withName('extended')
                ->ofType(Type::string())
                ->resolvedBy(fn() => 'extended'),
            fn(TypeRegistry $registry) => Field::withName('closure')
                ->ofType($registry->type(JsonScalar::class))
                ->resolvedBy(fn() => 'closure')
        );

        $registry->extend('User',Field::withName('byName')
            ->ofType(Type::string())
            ->resolvedBy(fn() => 'byName'));

        return $registry;
    }

    protected function queryType(): string
    {
        return QueryType::class;
    }

    public function testFieldExecution(): void
    {
        $result = $this->execute('query { currentUser }');
        $this->assertNoErrors($result);
        self::assertEquals('Hello World!', $result->data['currentUser']);
    }

    public function testFieldExecutionWithArguments(): void
    {
        $result = $this->execute('query { currentUser(name: "Doris") }');
        $this->assertNoErrors($result);
        self::assertEquals('Hello Doris', $result->data['currentUser']);
    }

    public function testSimpleExecution(): void
    {
        $result = $this->execute('query { whoami }');
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
    }

    public function testQueryWithType(): void
    {
        $result = $this->execute('query { whoami, user { id, data } }');
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
        self::assertEquals(QueryType::USER_ID, $result->data['user']['id']);
        self::assertIsArray($result->data['user']['data']);
    }

    public function testQueryWithArgument(): void
    {
        $result = $this->execute('query { whoami, user { id, data, name(name: "MY_NAME") } }');
        $this->assertNoErrors($result);
        self::assertEquals(QueryType::WHOAMI_DATA, $result->data['whoami']);
        self::assertEquals(QueryType::USER_ID, $result->data['user']['id']);
        self::assertEquals('MY_NAME', $result->data['user']['name']);
        self::assertIsArray($result->data['user']['data']);
    }

    public function testWithInputArgument(): void
    {
        $result = $this->execute('query { createAnimal(input: {id: "test"})}');
        $this->assertNoErrors($result);
        self::assertEquals('Done: test', $result->data['createAnimal']);
    }

    public function testQueryWithUnion(): void
    {
        $result = $this->execute('query { animals { ... on Lion {sound} } }');
        $this->assertNoErrors($result);
        self::assertCount(3, $result->data['animals']);
        $this->assertColumnCount(1, $result->data['animals'], 'sound');
    }

    public function testQueryWithScalarInput(): void
    {
        $result = $this->execute('query { json: jsonInput(json: "[1]") }');
        $this->assertNoErrors($result);
        self::assertIsArray($result->data['json']['json']);
        self::assertEquals(1, $result->data['json']['json'][0]);
    }

    public function testQueryWithInterface(): void
    {
        $result = $this->execute('query { mamels { sound } }');
        $this->assertNoErrors($result);
        self::assertCount(3, $result->data['mamels']);
        self::assertCount(3, $result->data['mamels']);
        $this->assertColumnCount(3, $result->data['mamels'], 'sound');
    }

    public function testQueryWithExtendedUserType(): void
    {
        $result = $this->execute('query { user { extended } }');
        $this->assertNoErrors($result);
        self::assertIsArray($result->data['user']);
        self::assertEquals('extended', $result->data['user']['extended']);
    }

    public function testQueryWithExtendedUserTypeAsClosure(): void
    {
        $result = $this->execute('query { user { closure } }');
        $this->assertNoErrors($result);
        self::assertIsArray($result->data['user']);
        self::assertEquals('closure', $result->data['user']['closure']);
    }

    public function testQueryWithExtendedUserTypeByName(): void
    {
        $result = $this->execute('query { user { byName } }');
        $this->assertNoErrors($result);
        self::assertIsArray($result->data['user']);
        self::assertEquals('byName', $result->data['user']['byName']);
    }
}
