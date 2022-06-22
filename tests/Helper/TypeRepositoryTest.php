<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Helper;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Types;
use PHPUnit\Framework\TestCase;

final class TypeRepositoryTest extends TestCase {

    private TypeRegistry $repository;

    protected function setUp(): void {
        $this->repository = new TypeRegistry([
            QueryType::typeName() => QueryType::class
        ]);
    }

    public function testType(): void {
        $type = $this->repository->type(QueryType::class);
        self::assertInstanceOf(Type::class, Types::enforceTypeLoading($type));

        self::assertInstanceOf(\Closure::class, $type);
        self::assertInstanceOf(\Closure::class, $this->repository->type(QueryType::class));

        self::assertSame(
            Types::enforceTypeLoading($type),
            Types::enforceTypeLoading($this->repository->type(QueryType::class))
        );
    }
}
