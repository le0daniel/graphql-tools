<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class CatType extends GraphQlType
{
    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('sound')
                ->ofType(new NonNull($registry->string()))
                ->resolvedBy(fn() => 'meow'),
        ];
    }

    protected function interfaces(): array
    {
        return [MamelInterface::class];
    }
}