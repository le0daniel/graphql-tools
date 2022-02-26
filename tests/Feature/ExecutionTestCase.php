<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Context;
use GraphQlTools\Execution\QueryExecutor;
use GraphQlTools\TypeRepository;
use PHPUnit\Framework\TestCase;

abstract class ExecutionTestCase extends TestCase {
    /**
     * Must return an instance of a repository
     *
     * @return TypeRepository
     */
    abstract protected function typeRepository(bool $withMetadataIntrospection = true): TypeRepository;

    /**
     * Defines the root query type
     *
     * @return string
     */
    abstract protected function queryType(): string;

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

    protected function assertError(ExecutionResult $result, string $expectedMessage): void {
        $errorMessages = [];

        foreach ($result->errors as $error) {
            if ($error->getMessage() === $expectedMessage) {
                self::assertTrue(true);
                return;
            }

            $errorClass = $error->getPrevious() ? get_class($error->getPrevious()) : get_class($error);
            $errorMessages[] = "{$errorClass}: {$error->getMessage()}";
        }

        self::fail(implode(PHP_EOL, $errorMessages));
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

    protected function execute(string $query, bool $withMetadataIntrospection = true) {
        $repository = $this->typeRepository($withMetadataIntrospection);
        $executor = new QueryExecutor(
            $repository->toSchema(
                $this->queryType(),
                $this->mutationType(),
                $this->eagerlyLoadedTypes(),
            ),
            $this->extensions()
        );
        return $executor->execute($query, new Context());
    }

    protected function tearDown(): void {}

}
