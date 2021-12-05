<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\TypeMetadata;
use GraphQlTools\LazyRepository;

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
            'currentUser' => CurrentUserField::class,
            'whoami' => [
                'type' => Type::string(),
                'resolve' => fn() => self::WHOAMI_DATA
            ],
            'user' => [
                'type' => new NonNull($this->typeRepository->type(UserType::class)),
                'resolve' => fn() => ['id' => self::USER_ID],
            ],
            'jsonInput' => [
                'type' => JsonScalar::class,
                'args' => [
                    'json' => new NonNull($this->typeRepository->type(JsonScalar::class))
                ],
                'resolve' => fn($d, array $arguments) => ['json' => $arguments['json']],
            ],
            'animals' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull($this->typeRepository->type(AnimalUnion::class)))),
                'resolve' => fn() => self::ANIMALS
            ],
            'mamels' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull($this->typeRepository->type(MamelInterface::class)))),
                'resolve' => fn() => self::ANIMALS
            ],

            $this->typeRepository instanceof LazyRepository
                ? TypeMetadata::toRootQueryField($this->typeRepository)
                : null,
        ];
    }

    protected function description(): string {
        return '';
    }
}
