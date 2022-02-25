<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\SimpleField;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\Test\Dummies\DummyAttribute;
use GraphQlTools\Utility\Fields;

#[DummyAttribute('this is my attribute')]
final class LionType extends GraphQlType {

    protected function fields(): array {
        return [
            SimpleField::withName('sound')
                ->withReturnType(Type::nonNull(Type::string()))
                ->withResolver(fn($data) => $data['sound']),

            SimpleField::withName('fieldWithMeta')
                ->withReturnType(Type::nonNull(Type::string()))
                ->withResolver(fn() => 'This is a test field')
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
