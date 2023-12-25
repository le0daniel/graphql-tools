<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema\Types;

use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class ClubType extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('id')
                ->ofType(new NonNull($registry->id())),
            Field::withName('name')
                ->ofType($registry->string()),
            Field::withName('city')->ofType($registry->string()),
        ];
    }
}