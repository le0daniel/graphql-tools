<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\SchemaForDataLoader;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

class IngredientType extends GraphQlType
{

    protected function fields(): array
    {
        return [
            Field::withName('id')
                ->ofType(Type::id())
                ->resolvedBy(fn($data): int => $data['id']),
            Field::withName('name')
                ->ofType(Type::string())
                ->resolvedBy(fn($data): string => $data['name'])
        ];
    }

    protected function description(): string
    {
        return '';
    }
}