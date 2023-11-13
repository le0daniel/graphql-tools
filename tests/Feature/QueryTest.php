<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\SchemaRegistry;
use GraphQlTools\Helper\Registry\TagBasedSchemaRules;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\LionType;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Test\Dummies\Schema\UserType;
use GraphQlTools\Utility\Middleware\Federation;
use GraphQlTools\Utility\TypeMap;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    protected function assertNoErrors(ExecutionResult $result): void
    {
        $message = '';
        foreach ($result->errors as $error) {
            $message .= $error->getMessage() . PHP_EOL;
        }

        self::assertEmpty($result->errors, $message);
    }

    protected function assertError(ExecutionResult $result, string $expectedMessage): void
    {
        $errorMessages = [];

        foreach ($result->errors as $error) {
            if ($error->getMessage() === $expectedMessage) {
                self::assertTrue(true);
                return;
            }

            $errorClass = $error->getPrevious() ? get_class($error->getPrevious()) : get_class($error);
            $errorMessages[] = "{$errorClass}: {$error->getMessage()}";
        }

        if (empty($errorMessages)) {
            self::fail("Expected error message: '{$expectedMessage}', did not get any errors");
        }

        self::fail(implode(PHP_EOL, $errorMessages));
    }

    protected function assertColumnCount(int $expectedCount, array $data, string $column): void
    {
        $count = 0;
        foreach ($data as $item) {
            if (!$item || (!is_array($item) && !$item instanceof \ArrayAccess)) {
                continue;
            }

            if (isset($item[$column])) {
                $count++;
            }
        }

        self::assertEquals($expectedCount, $count);
    }

    protected function executeOn(Schema $schema, string $query): ExecutionResult
    {
        $executor = new QueryExecutor(
            [],
        );
        return $executor->execute($schema, $query, new Context());
    }

    protected function execute(string $query): ExecutionResult
    {
        return $this->executeOn($this->schema(), $query);
    }

    protected function schemaRegistry(): SchemaRegistry
    {
        $federatedSchema = new SchemaRegistry();

        [$types, $extendedTypes] = TypeMap::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema');
        $federatedSchema->registerTypes($types);
        $federatedSchema->extendTypes($extendedTypes);

        $federatedSchema->extendType(
            UserType::class,
            fn(TypeRegistry $registry) => [
                Field::withName('extended')
                    ->ofType($registry->string())
                    ->resolvedBy(fn() => 'extended'),
                Field::withName('closure')
                    ->ofType($registry->type(JsonScalar::class))
                    ->resolvedBy(fn() => 'closure')
            ],
        );

        $federatedSchema->extendType('User', fn(TypeRegistry $registry) => [
            Field::withName('byName')
                ->ofType($registry->string())
                ->tags('name')
                ->middleware(
                    Federation::key('id')
                )
                ->resolvedBy(fn(string $id) => "byName: {$id}"),

            // Ensure circular dependencies work fine
            Field::withName('testCircular')
                ->ofType($registry->type('User')),

            Field::withName('lazy')
                ->ofType($registry->string())
                ->resolvedBy(fn() => 'lazy-field')
        ]);

        $federatedSchema->registerEagerlyLoadedType(LionType::class);

        return $federatedSchema;
    }

    protected function schema(array $excludeTags = []): Schema
    {
        $schema = $this
            ->schemaRegistry()
            ->createSchema(QueryType::class, schemaRules: new TagBasedSchemaRules($excludeTags));
        $schema->assertValid();
        return $schema;
    }

    public function testFieldExecution(): void
    {
        $result = $this->execute('query { currentUser @include(if: true) }');
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

    public function testMiddlewareWithoutResolver(): void
    {
        $result = $this->execute('query { middlewareWithoutResolver}');
        $this->assertNoErrors($result);
        self::assertEquals('Default - this is the middle', $result->data['middlewareWithoutResolver']);
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

    public function testQueryWithDirective(): void
    {
        $result = $this->execute('query { mamels { sound @include(if: true) } }');
        $this->assertNoErrors($result);
        self::assertCount(3, $result->data['mamels']);
        self::assertCount(3, $result->data['mamels']);
        $this->assertColumnCount(3, $result->data['mamels'], 'sound');
    }


    public function testQueryExtendedInterface(): void
    {
        $result = $this->execute('query { mamels { added } }');
        $this->assertNoErrors($result);
        self::assertCount(3, $result->data['mamels']);
        self::assertCount(3, $result->data['mamels']);
        $this->assertColumnCount(3, $result->data['mamels'], 'added');

        foreach ($result->data['mamels'] as $mamel) {
            self::assertEquals('this is a value', $mamel['added']);
        }
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

    public function testHiddenTaggedField(): void
    {
        $result = $this->executeOn(
            $this->schema(['private']),
            "query { middlewareWithPrimitiveBinding }"
        );
        $this->assertError($result, 'Cannot query field "middlewareWithPrimitiveBinding" on type "Query".');
    }

    public function testGlobalTypeMiddleware(): void
    {
        $result = $this->execute('{ protectedUser {secret} }');
        self::assertEquals('not allowed', $result->data['protectedUser']['secret']);
    }

    public function testHiddenTaggedInputField(): void
    {
        $result = $this->executeOn(
            $this->schema(['private']),
            "query { currentUser(name: \"my-name\") }"
        );
        $this->assertError($result, 'Unknown argument "name" on field "currentUser" of type "Query".');
    }

    public function testOverwrittenGetName(): void
    {
        $result = $this->executeOn(
            $this->schema(),
            "query { overwritten { id } }"
        );
        $this->assertNoErrors($result);
        self::assertEquals(['overwritten' => ['id' => 'super secret id']], $result->data);
    }

    public function testOverwrittenGetNameByAlias(): void
    {
        $result = $this->executeOn(
            $this->schema(),
            "query { overwrittenAlias { id } }"
        );
        $this->assertNoErrors($result);
        self::assertEquals(['overwrittenAlias' => ['id' => 'super secret id']], $result->data);
    }

    public function testFederationFieldMiddleware(): void
    {
        $result = $this->execute('query {testFieldMiddleware}');
        self::assertEquals('Hello -- No Name Provided --', $result->data['testFieldMiddleware']);
    }
}
