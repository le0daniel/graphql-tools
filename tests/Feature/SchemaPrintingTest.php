<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQL\Utils\SchemaPrinter;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Utility\TypeMap;
use PHPUnit\Framework\TestCase;

class SchemaPrintingTest extends TestCase
{

    public function testSchemaPrintsAreEqual(): void {
        $federatedSchema = new FederatedSchema();
        foreach (TypeMap::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema') as $name => $class) {
            $federatedSchema->registerType($name, $class);
        }

        $uncachedSchema = $federatedSchema->createSchema('Query');
        $cachedSchema = FederatedSchema::fromCachedSchema(eval($federatedSchema->cacheSchema()), 'Query');

        self::assertEquals(
            SchemaPrinter::doPrint($uncachedSchema),
            SchemaPrinter::doPrint($cachedSchema)
        );
    }

}