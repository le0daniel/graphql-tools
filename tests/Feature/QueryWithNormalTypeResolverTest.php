<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\Context;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRepository;

class QueryWithNormalTypeResolverTest extends ExecutionTestCase
{

    protected function typeRepository(bool $withMetadataIntrospection = true): TypeRepository
    {
        return new TypeRepository(
            TypeRepository::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema', $withMetadataIntrospection)
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
        $result = $this->execute('query { currentUser(name: "Me") }');
        $this->assertNoErrors($result);
        self::assertEquals('Hello Me', $result->data['currentUser']);
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

    public function testDisabledMetadataQueries(): void {
        $result = $this->execute('query {
            meta: __typeMetadata(name: "Lion") {
                name
            }
        }', false);

        self::assertNotEmpty($result->errors);
        self::assertNull($result->data);
    }

    public function testFieldMetadata(): void
    {
        $result = $this->execute('
            query {
                meta: __typeMetadata(name: "Lion") {
                    name
                    metadata
                    fields {
                        name
                        type
                        metadata
                    }
                    fieldByName(name: "fieldWithMeta") {
                        name
                        type
                        metadata
                    }
                    field2: fieldByName(name: "doesNotExist") {
                        name
                        type
                        metadata
                    }
                }
            }
        ');

        $this->assertNoErrors($result);
        self::assertEquals('Lion', $result->data['meta']['name']);
        self::assertEquals([
            "policies" => [
                "mamel:read" => "Must have the scope: `mamel:read` to access this property"
            ]
        ], $result->data['meta']['metadata']);
        self::assertNull($result->data['meta']['field2']);
        self::assertEquals([
            "policy" => 'This is my special policy'
        ], $result->data['meta']['fieldByName']['metadata']);
        self::assertEquals('String!', $result->data['meta']['fieldByName']['type']);
    }
}
