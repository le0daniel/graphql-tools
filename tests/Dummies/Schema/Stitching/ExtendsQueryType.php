<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Stitching;

use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\Field\Field;

final class ExtendsQueryType extends ExtendGraphQlType
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