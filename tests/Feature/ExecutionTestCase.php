<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Schema;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use PHPUnit\Framework\TestCase;

abstract class ExecutionTestCase extends TestCase {

    abstract protected function schema(): Schema;

    /**
     * Defines the root query type
     *
     * @return string
     */
    abstract protected function queryType(): string;

    protected function extensions(): array {
        return [];
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

        if (empty($errorMessages)) {
            self::fail("Expected error message: '{$expectedMessage}', did not get any errors");
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

    protected function executeOn(Schema $schema, string $query): ExecutionResult {
        $executor = new QueryExecutor(
            $this->extensions()
        );
        return $executor->execute($schema, $query, new Context());
    }

    protected function execute(string $query): ExecutionResult {
        return $this->executeOn($this->schema(), $query);
    }

    protected function tearDown(): void {}

}
