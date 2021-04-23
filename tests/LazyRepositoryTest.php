<?php

declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQlTools\LazyRepository;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use PHPUnit\Framework\TestCase;

final class LazyRepositoryTest extends TestCase {

    protected LazyRepository $repository;

    protected function setUp(): void {
        $this->repository = new LazyRepository(
            [
                QueryType::typeName() => QueryType::class,
            ]
        );
    }

    public function testLazyRepositoryReturnsFunctionForType(): void {
        $type = $this->repository->type(QueryType::class);
        self::assertInstanceOf(\Closure::class, $type);
        self::assertSame($type, $this->repository->type(QueryType::class));
    }

    public function testLazyRepositoryReturnsFunctionForTypeByName(): void {
        $type = $this->repository->type(QueryType::typeName());
        self::assertInstanceOf(\Closure::class, $type);
        self::assertSame($type, $this->repository->type(QueryType::class));
    }

    public function testResolutionOfType(): void {
        $type = $this->repository->type(QueryType::class);
        self::assertInstanceOf(\Closure::class, $type);
        self::assertSame($type(), $this->repository->type(QueryType::class)());
    }

}
