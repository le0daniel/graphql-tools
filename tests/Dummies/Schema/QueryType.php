<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;

final class QueryType extends GraphQlType {

    public const WHOAMI_DATA = 'Test';
    public const USER_ID = 'MQ==';
    public const ANIMALS = [
        ['type' => 'lion', 'sound' => 'Rooooahh'],
        ['type' => 'tiger', 'sound' => 'Raoooo'],
        ['type' => 'tiger', 'sound' => 'Raaggghhh'],
    ];

    protected function fields(): array {
        return [
            'whoami' => [
                'type' => Type::string(),
                'resolve' => fn() => self::WHOAMI_DATA
            ],
            'user' => [
                'type' => UserType::wrap(fn($type) => new NonNull($type)),
                'resolve' => fn() => ['id' => self::USER_ID],
            ],
            'jsonInput' => [
                'type' => JsonScalar::class,
                'args' => [
                    'json' => JsonScalar::wrap(fn($type) => new NonNull($type))
                ],
                'resolve' => fn($d, array $arguments) => ['json' => $arguments['json']],
            ],
            'animals' => [
                'type' => AnimalUnion::wrap(
                    fn($type) => Type::nonNull(Type::listOf(Type::nonNull($type)))
                ),
                'resolve' => fn() => self::ANIMALS
            ],
            'mamels' => [
                'type' => MamelInterface::wrap(
                    fn($type) => Type::nonNull(Type::listOf(Type::nonNull($type)))
                ),
                'resolve' => fn() => self::ANIMALS
            ],
        ];
    }

    protected function description(): string {
        return '';
    }
}
