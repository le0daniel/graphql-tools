<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Stitching;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\Field\Field;

final class ExtendsQueryType extends ExtendGraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('stitchedByClassName')
                ->ofType($registry->nonNull($registry->string()))
                ->resolvedBy(fn() => 'passed'),
        ];
    }
}