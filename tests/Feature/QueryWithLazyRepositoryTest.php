<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Feature;

use GraphQlTools\LazyRepository;
use GraphQlTools\Test\Dummies\Schema\AnimalUnion;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\LionType;
use GraphQlTools\Test\Dummies\Schema\MamelInterface;
use GraphQlTools\Test\Dummies\Schema\QueryType;
use GraphQlTools\Test\Dummies\Schema\TigerType;
use GraphQlTools\Test\Dummies\Schema\UserType;
use GraphQlTools\TypeRepository;

final class QueryWithLazyRepositoryTest extends QueryWithNormalTypeResolverTest {

    protected function typeRepository(): TypeRepository {
        return new LazyRepository(
            LazyRepository::createTypeMapFromDirectory(__DIR__ . '/../Dummies/Schema')
        );
    }

}
