<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use Closure;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Test\Dummies\SchemaForDataLoader\QueryType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SchemaWithDataLoaderTest extends TestCase
{
    public static function data(): array {
        return [
            1 => ['id' => 1, 'name' => 'my-name-1'],
            2 => ['id' => 2, 'name' => 'my-name-2'],
            3 => new RuntimeException('Not Found'),
        ];
    }

    private TypeRegistry $typeRegistry;
    private QueryExecutor $executor;

    protected function setUp(): void
    {
        $this->typeRegistry = new TypeRegistry(
            TypeRegistry::createTypeMapFromDirectory(__DIR__ . '/../Dummies/SchemaForDataLoader'),
        );
        $this->executor = new QueryExecutor();
    }

    private function createContext(): Context {
        return new class () extends Context {
            protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure
            {
                return match ($classNameOrLoaderName) {
                    'loadMany' => static function () {
                        return SchemaWithDataLoaderTest::data();
                    }
                };
            }
        };
    }

    private function loadByIds(int ... $ids): ExecutionResult {
        return $this->executor->execute(
            $this->typeRegistry->toSchema(QueryType::class),
            'query ($ids: [ID!]!) {
                ingredients: loadByIds(ids: $ids) {
                    id
                    name
                }
            }',
            context: $this->createContext(),
            variables: [
                'ids' => $ids
            ],
        );
    }

    public function testWithLoadManyDataLoader(): void {
        self::assertEquals([
            'ingredients' => [
                ['id' => 1, 'name' => 'my-name-1'],
                ['id' => 2, 'name' => 'my-name-2'],
            ]
        ], $this->loadByIds(1, 2)->data);
    }

    public function testWithLoadManyNotFound(): void {
        $result = $this->loadByIds(1, 3);

        self::assertEquals([
            'ingredients' => [
                ['id' => 1, 'name' => 'my-name-1'],
                null
            ]
        ], $result->data);

        self::assertCount(1, $result->errors);
        self::assertInstanceOf(Error::class, $result->errors[0]);
        self::assertEquals('Not Found', $result->errors[0]->getMessage());
    }

    public function testWithLoadWithoutError(): void {
        $result = $this->loadByIds(2, 4);

        self::assertEquals([
            'ingredients' => [
                ['id' => 2, 'name' => 'my-name-2'],
                null
            ]
        ], $result->data);
    }

}