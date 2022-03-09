<?php

declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Types;
use PHPUnit\Framework\TestCase;

final class TypeRepositoryTest extends TestCase {

    private TypeRepository $repository;

    protected function setUp(): void {
        $this->repository = new TypeRepository([
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
