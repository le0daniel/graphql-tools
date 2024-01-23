<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Stitching;

use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendType;
use GraphQlTools\Definition\Field\Field;

final class ExtendsQueryType extends ExtendType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('stitchedByClassName')
                ->ofType(new NonNull($registry->string()))
                ->resolvedBy(fn() => 'passed'),
        ];
    }
}