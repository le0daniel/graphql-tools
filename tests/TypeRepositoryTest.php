<?php

declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\TypeRepository;
use PHPUnit\Framework\TestCase;

final class TypeRepositoryTest extends TestCase {

    private TypeRepository $repository;

    protected function setUp(): void {
        $this->repository = new TypeRepository();
    }

    public function testListOfType(): void {
        $type = $this->repository->listOfType(QueryType::class);
        self::assertEquals(
            $type->getWrappedType(),
            $this->repository->listOfType(QueryType::class)->getWrappedType()
        );
    }

    public function testType(): void {
        $type = $this->repository->type(QueryType::class);
        self::assertInstanceOf(Type::class, $type);
        self::assertSame($type, $this->repository->type(QueryType::class));
    }
}
