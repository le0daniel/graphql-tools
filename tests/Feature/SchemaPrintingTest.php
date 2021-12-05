<?php declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRepository;
use PHPUnit\Framework\TestCase;

final class SchemaPrintingTest extends TestCase
{

    public function testPrintingWithoutMetadata(): void {
        $schema = (new TypeRepository(
            TypeRepository::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema', false)
        ))->toSchema(QueryType::class);

        $schemaContent = TypeRepository::print($schema);
        self::assertFalse(str_contains($schemaContent, '__typeMetadata'));
    }

    public function testPrintingWithMetadata(): void {
        $schema = (new TypeRepository(
            TypeRepository::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema', true)
        ))->toSchema(QueryType::class);

        $schemaContent = TypeRepository::print($schema);
        self::assertTrue(str_contains($schemaContent, '__typeMetadata'));
    }

}