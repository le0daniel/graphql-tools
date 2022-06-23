<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\Enum\Eating;

final class LionType extends GraphQlType {

    protected function fields(): array {
        return [
            Field::withName('sound')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(fn($data) => $data['sound']),

            Field::withName('fieldWithMeta')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(function ($value, $args, $context, ResolveInfo $resolveInfo) {
                    return "policy is: " . $resolveInfo->fieldDefinition->config['metadata']['policy'];
                })
                ->withMetadata([
                    'policy' => 'This is my special policy'
                ]),

            Field::withName('myEnum')
                ->ofType($this->typeRegistry->type(EatingEnum::class))
                ->resolvedBy(fn() => Eating::MEAT),
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
