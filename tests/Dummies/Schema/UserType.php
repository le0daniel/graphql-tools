<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\SimpleField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\TypeRepository;

final class UserType extends GraphQlType {

    protected function fields(): array {
        return [
            SimpleField::withName('id')
                ->ofType(Type::id())
                ->withResolver(fn($data) => $data['id']),

            SimpleField::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->withArguments(
                    Argument::withName('name')->ofType( Type::string())
                )
                ->withResolver(fn($data, array $arguments) => $arguments['name'] ?? 'no name given'),

            SimpleField::withName('data')
                ->ofType(fn(TypeRepository $typeRepository) => $typeRepository->type(JsonScalar::class))
                ->withResolver(fn() => ['test' => ['json' => [1, 2, 3, 4]]])
            ,
        ];
    }

    protected function description(): string {
        return '';
    }
}
