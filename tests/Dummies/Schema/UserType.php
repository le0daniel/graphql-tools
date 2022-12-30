<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;

final class UserType extends GraphQlType {

    protected function fields(TypeRegistry $registry): array {
        return [
            Field::withName('id')
                ->ofType(Type::id())
                ->resolvedBy(fn($data) => $data['id']),

            Field::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->withArguments(
                    InputField::withName('name')->ofType( Type::string())
                )
                ->resolvedBy(fn($data, array $arguments) => $arguments['name'] ?? 'no name given'),

            Field::withName('data')
                ->ofType($registry->type(JsonScalar::class))
                ->resolvedBy(fn() => ['test' => ['json' => [1, 2, 3, 4]]])
            ,
        ];
    }

    protected function description(): string {
        return '';
    }
}
