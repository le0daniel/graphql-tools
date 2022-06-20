<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\Schema\Input\MamelsQueryInputType;
use GraphQlTools\TypeRegistry;

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
            Field::withName('currentUser')
                ->ofType(Type::string())
                ->ofSchemaVariant('MySchemaVariant::hides it on other schemas')
                // Alternative if type repo is needed
                // ->ofType(fn(TypeRepository $typeRepository) => new NonNull(Type::string()))
                ->withArguments(
                    InputField::withName('name')
                        ->ofType(Type::string())
                )
                ->deprecated('My reason')
                ->withDescription('')
                ->resolvedBy(function($data, $arguments){
                    if ($arguments['name'] ?? null) {
                        return "Hello {$arguments['name']}";
                    }

                    return 'Hello World!';
                })
            ,

            Field::withName('mamelsQuery')
                ->ofType(Type::string())
                ->withArguments(
                    InputField::withName('query')
                        ->ofType(MamelsQueryInputType::class)
                )
                ->resolvedBy(fn($data, array $args) => 'My result: ' . ($args['query']['name'] ?? '-- no query --')),

            Field::withName('mamels')
                ->ofType(
                    static fn(TypeRegistry $typeRepository) => Type::nonNull(Type::listOf(Type::nonNull($typeRepository->type(MamelInterface::class))))
                )
                ->resolvedBy(function ($item, array $arguments, Context $context) {
                    return self::ANIMALS;
                }),


            Field::withName('whoami')
                ->ofType(Type::string())
                ->resolvedBy(fn() => self::WHOAMI_DATA),

            Field::withName('createAnimal')
                ->ofType(Type::string())
                ->withArguments(
                    InputField::withName('input')->ofType(CreateAnimalInputType::class)
                )->resolvedBy(
                    fn($data, array $arguments) => "Done: {$arguments['input']['id']}"
                ),

            Field::withName('user')
                ->ofType(fn(TypeRegistry $typeRepository) => new NonNull($typeRepository->type(UserType::class)))
                ->resolvedBy(fn() => ['id' => self::USER_ID]),

            Field::withName('jsonInput')
                ->ofType(JsonScalar::class)
                ->withArguments(
                    InputField::withName('json')
                        ->ofType(fn(TypeRegistry $typeRepository) => new NonNull($typeRepository->type(JsonScalar::class)))
                )
                ->resolvedBy(fn($d, array $arguments) => ['json' => $arguments['json']])
            ,

            Field::withName('animals')
                ->ofType(fn(TypeRegistry $typeRepository) => Type::nonNull(Type::listOf(Type::nonNull($typeRepository->type(AnimalUnion::class)))))
                ->resolvedBy(fn() => self::ANIMALS)
            ,
        ];
    }

    protected function description(): string {
        return '';
    }
}
