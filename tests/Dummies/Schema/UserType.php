<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;

final class UserType extends GraphQlType {

    protected function fields(): array {
        return [
            'id' => Type::id(),
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'args' => [
                    'name' => Type::string(),
                ],
                'resolve' => fn($data, array $arguments) => $arguments['name'] ?? 'no name given',
            ],
            'data' => [
                'type' => JsonScalar::class,
                'resolve' => fn() => ['test' => ['json' => [1, 2, 3, 4]]],
            ],
        ];
    }

    protected function description(): string {
        return '';
    }
}
