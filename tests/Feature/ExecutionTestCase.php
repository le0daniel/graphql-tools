<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Execution\QueryExecutor;
use GraphQlTools\TypeRepository;
use PHPUnit\Framework\TestCase;

abstract class ExecutionTestCase extends TestCase {

    protected QueryExecutor $queryExecutor;

    /**
     * Must return an instance of a repository
     *
     * @return TypeRepository
     */
    abstract protected function typeRepository(): TypeRepository;

    /**
     * Defines the root query type
     *
     * @return string
     */
    abstract protected function queryType(): string;

    /**
     * Returns the mode in which the types are defined, either classname of typename
     * @return string
     */
    protected function mode(): string {
        return 'classname';
    }

    protected function extensions(): ?array {
        return null;
    }

    protected function mutationType(): ?string {
        return null;
    }

    protected function eagerlyLoadedTypes(): array {
        return [];
    }

    protected function assertNoErrors(ExecutionResult $result): void {
        $message = '';
        foreach ($result->errors as $error) {
            $message .= $error->getMessage() . PHP_EOL;
        }

        self::assertEmpty($result->errors, $message);
    }

    protected function assertColumnCount(int $expectedCount, array $data, string $column): void {
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

    protected function setUp(): void {
        $this->queryExecutor = new QueryExecutor(
            $this->typeRepository()->toSchema(
                $this->queryType(),
                $this->mutationType(),
                $this->eagerlyLoadedTypes(),
            ),
            $this->extensions(),
        );
    }

}
