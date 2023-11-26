<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class DepthType extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('id')
                ->ofType($registry->string())
                ->resolvedBy(fn() => 'just a string'),
            Field::withName('deeper')
                ->ofType($registry->type(DepthType::class))
                ->resolvedBy(function ($_, $__, $c, ResolveInfo $info): string {
                    return 'some-data';
                })
        ];
    }

    protected function description(): string
    {
        return '';
    }
}