<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class TypeWithOtherNamePattern extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('id')
                ->ofType($registry->id())
                ->resolvedBy(fn() => "super secret id"),
        ];
    }

    protected function description(): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'OverwrittenName';
    }
}