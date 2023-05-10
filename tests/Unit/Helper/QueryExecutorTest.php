<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQlTools\Contract\ExtendsResult;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\FederatedSchema;
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

        $federatedSchema = new FederatedSchema();
        $federatedSchema->registerTypes(TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema'));
        $schema = $federatedSchema->createSchema('Query');

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

        $federatedSchema = new FederatedSchema();
        $federatedSchema->registerTypes(TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema'));
        $schema = $federatedSchema->createSchema('Query');

        $result = $queryExecutor->execute(
            $schema,
            'query { mamels { sound, ... on Lion { depth { deeper { id } } } } }',
            new Context()
        );

        self::assertEquals('Max query depth should be 1 but got 2.',$result->errors[0]->getMessage());
    }

    public function testWithContextualValidationRule() {
        $rule = new class extends ValidationRule implements ExtendsResult {

            public function isVisibleInResult($context): bool
            {
                return true;
            }

            public function key(): string
            {
                return 'test';
            }

            public function jsonSerialize(): string
            {
                return 'result';
            }
        };
        $queryExecutor = new QueryExecutor(
            [],
            [$rule::class]
        );

        $federatedSchema = new FederatedSchema();
        $federatedSchema->registerTypes(TypeMap::createTypeMapFromDirectory(__DIR__ . '/../../Dummies/Schema'));
        $schema = $federatedSchema->createSchema('Query');

        $result = $queryExecutor->execute(
            $schema,
            'query { currentUser }',
            new Context()
        );

        self::assertEquals('result', $result->extensions['test']->jsonSerialize());
    }
}
