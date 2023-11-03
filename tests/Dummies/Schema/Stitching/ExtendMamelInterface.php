<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Stitching;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\Field\Field;

class ExtendMamelInterface extends ExtendGraphQlType
{

    protected function key(): ?string
    {
        return null;
    }

    public function typeName(): string
    {
        return 'Mamel';
    }

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('added')
                ->ofType($registry->string())
                ->resolvedBy(fn() => 'this is a value')
        ];
    }
}