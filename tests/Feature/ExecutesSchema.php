<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Registry\FederatedSchema;
use PHPUnit\Framework\TestCase;

abstract class ExecutesSchema extends TestCase
{

    abstract function registerSchema(FederatedSchema $schema): void;
    protected function executeQuery(string $query): ExecutionResult {
        $federatedSchema = new FederatedSchema();
        $this->registerSchema($federatedSchema);

        $queryExecutor = new QueryExecutor([], [], null);
        return $queryExecutor->execute(
            $federatedSchema->createSchema('Query'),
            $query,
            new Context(),
        );
    }

}