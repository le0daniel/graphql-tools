<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Validation;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Helper\Validation\QueryComplexityRule;
use PHPUnit\Framework\TestCase;

class QueryComplexityRuleTest extends TestCase
{
    private function getSchema(): Schema {
        $federatedSchema = new FederatedSchema();
        $federatedSchema->register(new class () extends GraphQlType {
            protected function fields(TypeRegistry $registry): array
            {
                return [
                    Field::withName('currentUser')
                        ->ofType(Type::string())
                        ->cost(5)
                        ->resolvedBy(fn() => 'username'),
                    Field::withName('user')
                        ->ofType($registry->type('User'))
                        ->cost(3)
                        ->resolvedBy(fn() => 'something'),
                    Field::withName('freeUser')
                        ->ofType($registry->type('User'))
                        ->resolvedBy(fn() => 'something')
                ];
            }

            protected function description(): string
            {
                return '';
            }

            public function getName(): string
            {
                return 'Query';
            }
        });
        $federatedSchema->register(new class() extends GraphQlType {

            protected function fields(TypeRegistry $registry): array
            {
                return [
                    Field::withName('id')
                        ->ofType(Type::string())
                        ->cost(5)
                        ->resolvedBy(fn() => 'username'),
                    Field::withName('name')
                        ->ofType(Type::string())
                        ->cost(3)
                        ->resolvedBy(fn() => 'username'),
                ];
            }

            protected function description(): string
            {
                return '';
            }

            public function getName(): string
            {
                return 'User';
            }
        });
        return $federatedSchema->createSchema('Query');
    }
    private function getExecutor(int $maxComplexity): QueryExecutor {
        return new QueryExecutor(validationRules: [
            static fn() => new QueryComplexityRule($maxComplexity),
        ]);
    }

    private function query(string $query, int $maxComplexity): ExecutionResult {
        return $this->getExecutor($maxComplexity)
            ->execute(
                $this->getSchema(),
                $query,
                new Context()
            );
    }

    private function cost(string $query): int {
        $result = $this->query($query, 100000);
        /** @var QueryComplexityRule $queryComplexity */
        $queryComplexity = $result->extensions['complexity'];
        return $queryComplexity->getActualComplexity();
    }

    public function testMaxQueryScore(): void {
        $result = $this->query('query { currentUser }', 10);
        /** @var QueryComplexityRule $queryComplexity */
        $queryComplexity = $result->extensions['complexity'];

        self::assertEquals(5, $queryComplexity->getActualComplexity());
        self::assertEquals(10, $queryComplexity->getMaxQueryComplexity());
        self::assertEmpty($result->errors);
    }

    public function testMaxQueryScoreExceeded(): void {
        $result = $this->query('query { currentUser }', 4);
        /** @var QueryComplexityRule $queryComplexity */
        $queryComplexity = $result->extensions['complexity'];

        self::assertEquals(5, $queryComplexity->getActualComplexity());
        self::assertEquals(4, $queryComplexity->getMaxQueryComplexity());
        self::assertEmpty($result->data);
        self::assertEquals("Max query complexity should be 4 but got 5.", $result->errors[0]->getMessage());
    }

    public function testUserQueryCost(): void {
        self::assertEquals(8, $this->cost('query { user {id} }'));
        self::assertEquals(11, $this->cost('query { user {id, name} }'));
        self::assertEquals(8, $this->cost('query { freeUser {id, name} }'));
        self::assertEquals(5, $this->cost('query { freeUser {id} }'));
    }

}
