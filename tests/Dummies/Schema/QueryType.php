<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\DeferredField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\TypeRepository;

final class QueryType extends GraphQlType {

    public const WHOAMI_DATA = 'Test';
    public const USER_ID = 'MQ==';
    public const ANIMALS = [
        ['id' => 1, 'type' => 'lion', 'sound' => 'Rooooahh'],
        ['id' => 2, 'type' => 'tiger', 'sound' => 'Raoooo'],
        ['id' => 3, 'type' => 'tiger', 'sound' => 'Raaggghhh'],
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
            DeferredField::withName('mamels')
                ->withReturnType(static fn(TypeRepository $typeRepository) => Type::nonNull(Type::listOf(Type::nonNull($typeRepository->type(MamelInterface::class)))))
                ->resolveAggregated(function(array $aggregatedItems, array $arguments, Context $context){
                    return self::ANIMALS;
                })
                ->resolveItem(function ($item, array $data, Context $context) {
                    return $data;
                }),

        ];
    }

    protected function description(): string {
        return '';
    }
}
