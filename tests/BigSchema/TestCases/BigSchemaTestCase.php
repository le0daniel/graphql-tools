<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema\TestCases;

use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\SchemaRegistry;
use GraphQlTools\Helper\Results\CompleteResult;
use GraphQlTools\Test\BigSchema\BigSchemaContext;
use GraphQlTools\Utility\Time;
use GraphQlTools\Utility\TypeMap;
use PHPUnit\Framework\TestCase;

abstract class BigSchemaTestCase extends TestCase
{
    private SchemaRegistry $schemaRegistry;
    private QueryExecutor $executor;

    protected function setUp(): void
    {
        $this->executor = new QueryExecutor([], []);
        $this->schemaRegistry = new SchemaRegistry();

        $typesAndExtensions = TypeMap::createTypeMapFromDirectory(__DIR__ . '/../');
        $this->schemaRegistry->registerFromTypeMap(...$typesAndExtensions);
    }

    /** @return array{0: CompleteResult, 1: float} */
    protected function execute(string $query, ?array $variables = null): CompleteResult
    {
        /** @var CompleteResult $result */
        [$durationInSeconds, $result] = Time::measure(fn() => $this->executor->execute(
            $this->schemaRegistry->createSchema('Query'),
            $query,
            new BigSchemaContext(),
            $variables,
        ));

        if ($durationInSeconds > 0.05) {
            $this->addWarning("Duration of the query was longer than 50ms (0.05s): {$durationInSeconds}s");
        }

        return $result;
    }

    protected function assertNoErrors(CompleteResult $result): void {
        if (count($result->getErrors()) > 0) {
            $errorsCount = count($result->getErrors());
            self::fail("Expected no errors from query, got: {$errorsCount}.");
        }
        self::assertTrue(true);
    }

}