<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\LionType;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Test\Dummies\Schema\UserType;
use GraphQlTools\Utility\Middleware\Federation;
use GraphQlTools\Utility\TypeMap;

class QueryTest extends ExecutionTestCase
{
    protected function federatedSchema(): FederatedSchema {
        $federatedSchema = new FederatedSchema();
        $federatedSchema->registerTypes(TypeMap::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema'));

        $federatedSchema->extendType(
            UserType::class,
            fn(TypeRegistry $registry) => [
                Field::withName('extended')
                    ->ofType(Type::string())
                    ->resolvedBy(fn() => 'extended'),
                Field::withName('closure')
                    ->ofType($registry->type(JsonScalar::class))
                    ->resolvedBy(fn() => 'closure')
            ],
        );

        $federatedSchema->extendType('User',fn(TypeRegistry $registry) => [
            Field::withName('byName')
                ->ofType(Type::string())
                ->tags('name')
                ->middleware(
                    Federation::key('id')
                )
                ->resolvedBy(fn(string $id) => "byName: {$id}"),

            // Ensure circular dependencies work fine
            Field::withName('testCircular')
                ->ofType($registry->type('User')),

            Field::withName('lazy')
                ->ofType(Type::string())
                ->resolvedBy(fn() => 'lazy-field')
        ]);

        $federatedSchema->registerEagerlyLoadedType(LionType::class);

        return $federatedSchema;
    }

    protected function schema(array $excludeTags = []): Schema
    {
        $schema = $this
            ->federatedSchema()
            ->createSchema(QueryType::class, excludeTags: $excludeTags);
        $schema->assertValid();
        return $schema;
    }

    protected function queryType(): string
    {
        return QueryType::class;
    }

    public function testFieldExecution(): void
    {
        $result = $this->execute('query { currentUser }');
        $this->assertNoErrors($result);
        self::assertEquals('Hello -- No Name Provided --', $result->data['currentUser']);
    }

    public function testFieldExecutionWithArguments(): void
    {
        $result = $this->execute('query { currentUser(name: "Doris") }');
        $this->assertNoErrors($result);
        self::assertEquals('Hello Doris', $result->data['currentUser']);
    }

    public function testFieldExecutionWithContextualValueResolver(): void
    {
        $result = $this->execute('query { middlewareWithPrimitiveBinding }');
        $this->assertNoErrors($result);
        self::assertEquals('test', $result->data['middlewareWithPrimitiveBinding']);
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
        self::assertEquals('byName: MQ==', $result->data['user']['byName']);
    }

    public function testQueryWithLazyExtendedUserType(): void
    {
        $result = $this->execute('query { user { lazy } }');
        $this->assertNoErrors($result);
        self::assertIsArray($result->data['user']);
        self::assertEquals('lazy-field', $result->data['user']['lazy']);
    }

    public function testHiddenTaggedField(): void {
        $result = $this->executeOn(
            $this->schema(['private']),
            "query { middlewareWithPrimitiveBinding }"
        );
        $this->assertError($result, 'Cannot query field "middlewareWithPrimitiveBinding" on type "Query".');
    }

    public function testHiddenTaggedInputField(): void {
        $result = $this->executeOn(
            $this->schema(['private']),
            "query { currentUser(name: \"my-name\") }"
        );
        $this->assertError($result, 'Unknown argument "name" on field "currentUser" of type "Query".');
    }

    // public function testQueryWithBuilderField(): void
    // {
    //     $result = $this->execute('query { builderField { test } }');
    //     $this->assertNoErrors($result);
    //     self::assertIsArray($result->data['builderField']);
    //     self::assertEquals('This is a test', $result->data['builderField']['test']);
    // }

}
