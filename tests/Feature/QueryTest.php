<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\Helper\Context;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Helper\TypeRegistry;

class QueryTest extends ExecutionTestCase
{

    protected function typeRepository(bool $withMetadataIntrospection = true): TypeRegistry
    {
        return new TypeRegistry(
            TypeRegistry::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema')
        );
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
}
