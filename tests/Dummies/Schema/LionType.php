<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\SimpleField;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\Utility\Fields;

final class LionType extends GraphQlType {

    protected function fields(): array {
        return [
            SimpleField::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn($data) => $data['sound']),

            SimpleField::withName('fieldWithMeta')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn() => 'This is a test field')
                ->withMetadata([
                    'policy' => 'This is my special policy'
                ]),
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
