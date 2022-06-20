<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRegistry;
use PHPUnit\Framework\TestCase;

final class SchemaPrintingTest extends TestCase
{

    public function testPrintingWithoutMetadata(): void {
        $schema = (new TypeRegistry(
            TypeRegistry::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema', false)
        ))->toSchema(QueryType::class);

        $schemaContent = TypeRegistry::print($schema);
        self::assertFalse(str_contains($schemaContent, '__typeMetadata'));
    }

}