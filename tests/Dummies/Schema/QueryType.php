<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\Schema\Input\MamelsQueryInputType;
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
            Field::withName('currentUser')
                ->ofType(Type::string())
                // Alternative if type repo is needed
                // ->ofType(fn(TypeRepository $typeRepository) => new NonNull(Type::string()))
                ->withArguments(
                    Argument::withName('name')
                        ->ofType(Type::string())
                        ->withValidator(function(mixed $value) {
                            if (!$value) {
                                return $value;
                            }

                            if (strlen($value) < 5) {
                                throw new \Exception("Invalid, string to short: '{$value}'");
                            }
                            return $value;
                        })
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
                    Argument::withName('query')
                        ->ofType(MamelsQueryInputType::class)
                )
                ->resolvedBy(fn($data, array $args) => 'My result: ' . ($args['query']['name'] ?? '-- no query --')),

            Field::withName('mamels')
                ->ofType(
                    static fn(TypeRepository $typeRepository) => Type::nonNull(Type::listOf(Type::nonNull($typeRepository->type(MamelInterface::class))))
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
                    Argument::withName('input')->ofType(CreateAnimalInputType::class)
                )->resolvedBy(
                    fn($data, array $arguments) => "Done: {$arguments['input']['id']}"
                ),

            Field::withName('user')
                ->ofType(fn(TypeRepository $typeRepository) => new NonNull($typeRepository->type(UserType::class)))
                ->resolvedBy(fn() => ['id' => self::USER_ID]),

            Field::withName('jsonInput')
                ->ofType(JsonScalar::class)
                ->withArguments(
                    Argument::withName('json')
                        ->ofType(fn(TypeRepository $typeRepository) => new NonNull($typeRepository->type(JsonScalar::class)))
                )
                ->resolvedBy(fn($d, array $arguments) => ['json' => $arguments['json']])
            ,

            Field::withName('animals')
                ->ofType(fn(TypeRepository $typeRepository) => Type::nonNull(Type::listOf(Type::nonNull($typeRepository->type(AnimalUnion::class)))))
                ->resolvedBy(fn() => self::ANIMALS)
            ,
        ];
    }

    protected function description(): string {
        return '';
    }
}
