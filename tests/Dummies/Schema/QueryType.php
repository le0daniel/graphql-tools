<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;

final class QueryType extends GraphQlType {

    public const WHOAMI_DATA = 'Test';
    public const USER_ID = 'MQ==';

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
            'animals' => [
                'type' => AnimalUnion::wrap(
                    fn($type) => Type::nonNull(Type::listOf(Type::nonNull($type)))
                ),
                'resolve' => fn() => [
                    ['type' => 'lion', 'sound' => 'Rooooahh'],
                    ['type' => 'tiger', 'sound' => 'Raoooo'],
                    ['type' => 'tiger', 'sound' => 'Raaggghhh'],
                ]
            ]
        ];
    }

    protected function description(): string {
        return '';
    }
}
