<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;

final class LionType extends GraphQlType {

    protected function fields(): array {
        return [
            'sound' => Type::nonNull(Type::string()),
            'fieldWithMeta' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => fn() => 'This is a test field',
                GraphQlField::METADATA_CONFIG_KEY => [
                    'policy' => 'This is my special policy'
                ]
            ]
        ];
    }

    protected function interfaces(): array {
        return [MamelInterface::class];
    }

    protected function description(): string {
        return '';
    }

    protected function metadata(): array
    {
        return [
            'policies' => [
                'mamel:read' => 'Must have the scope: `mamel:read` to access this property'
            ],
        ];
    }
}
