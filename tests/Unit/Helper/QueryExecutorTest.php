<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Error\DebugFlag;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\SchemaRegistry;
use GraphQlTools\Utility\TypeMap;
use PHPUnit\Framework\TestCase;

class QueryExecutorTest extends TestCase
{
    public function testExecuteWithValidationRuleFactory()
    {
        $instance = new QueryDepth(1);
        $queryExecutor = new QueryExecutor(
            [],
            [fn() => $instance]
        );

        $schemaRegistry = new SchemaRegistry();
        [$types, $extendedTypes] = TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema');
        $schemaRegistry->registerTypes($types);
        $schemaRegistry->extendTypes($extendedTypes);
        $schema = $schemaRegistry->createSchema('Query');

        $query = 'query { mamels { sound, ... on Lion { depth { deeper { id } } } } }';
        $result = $queryExecutor->execute(
            $schema,
            $query,
            new Context()
        );

        $validationResult = $queryExecutor->validateQuery($schema, $query);
        self::assertTrue($validationResult->hasErrors());
        self::assertInstanceOf(QueryDepth::class, $validationResult->getRule(QueryDepth::class));
        self::assertCount(1, $validationResult->errors);
        self::assertSame($instance, $validationResult->getRule(QueryDepth::class));

        self::assertEquals('Max query depth should be 1 but got 2.',$result->errors[0]->getMessage());
    }

    public function testExecuteWithValidationRuleInstance()
    {
        $queryExecutor = new QueryExecutor(
            [],
            [new QueryDepth(1)]
        );

        $schemaRegistry = new SchemaRegistry();
        [$types, $extendedTypes] = TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema');
        $schemaRegistry->registerTypes($types);
        $schemaRegistry->extendTypes($extendedTypes);
        $schema = $schemaRegistry->createSchema('Query');

        $result = $queryExecutor->execute(
            $schema,
            'query { mamels { sound, ... on Lion { depth { deeper { id } } } } }',
            new Context()
        );

        self::assertEquals('Max query depth should be 1 but got 2.',$result->errors[0]->getMessage());
    }

    public function testWithContextualValidationRule() {


        $rule = new class extends ValidationRule implements ProvidesResultExtension {

            public function isVisibleInResult($context): bool
            {
                return true;
            }

            public function key(): string
            {
                return 'test';
            }

            public function serialize(int $debug = DebugFlag::NONE): mixed
            {
                return 'result';
            }
        };
        $queryExecutor = new QueryExecutor(
            [],
            [$rule::class]
        );

        $schemaRegistry = new SchemaRegistry();
        [$types, $extendedTypes] = TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema');
        $schemaRegistry->registerTypes($types);
        $schemaRegistry->extendTypes($extendedTypes);
        $schema = $schemaRegistry->createSchema('Query');

        $result = $queryExecutor->execute(
            $schema,
            'query { currentUser }',
            new Context()
        );

        self::assertEquals('result', $result->toArray()['extensions']['test']);
    }
}
