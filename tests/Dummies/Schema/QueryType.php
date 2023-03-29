<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\Context;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Test\Dummies\Schema\Input\MamelsQueryInputType;

final class QueryType extends GraphQlType
{

    public const WHOAMI_DATA = 'Test';
    public const USER_ID = 'MQ==';
    public const ANIMALS = [
        ['id' => 1, 'type' => 'lion', 'sound' => 'Rooooahh'],
        ['id' => 2, 'type' => 'tiger', 'sound' => 'Raoooo'],
        ['id' => 3, 'type' => 'tiger', 'sound' => 'Raaggghhh'],
    ];

    public static function defaultValue(string $key, string $defaultValue): \Closure
    {
        return static function ($_, array $args, $context, $info, $next) use ($key, $defaultValue) {
            $args[$key] = $args[$key] ?? $defaultValue;
            return $next(null, $args, $context, $info);
        };
    }

    public static function noValue($_, $args, $context, $info, $next)
    {
        return $next(null, $args, $context, $info);
    }


    protected function fields(TypeRegistry $registry): array
    {
        $value = 'test';
        return [
            Field::withName('currentUser')
                ->ofType(Type::string())
                ->withArguments(
                    InputField::withName('name')
                        ->tags('private')
                        ->ofType(Type::string())
                )
                ->deprecated('My reason')
                ->withDescription('')
                ->middleware(
                    self::defaultValue('name', '-- No Name Provided --')
                )
                ->resolvedBy(function ($data, $arguments) {
                    if ($arguments['name'] ?? null) {
                        return "Hello {$arguments['name']}";
                    }

                    return 'Hello World!';
                }),

            Field::withName('middlewareWithPrimitiveBinding')
                ->ofType(Type::string())
                ->tags('private')
                ->resolvedBy(fn() => $value),

            Field::withName('mamelsQuery')
                ->ofType(Type::string())
                ->withArguments(
                    InputField::withName('query')
                        ->ofType(MamelsQueryInputType::class)
                )
                ->resolvedBy(fn($data, array $args) => 'My result: ' . ($args['query']['name'] ?? '-- no query --')),

            Field::withName('mamels')
                ->ofType(
                    new ListOfType($registry->type(MamelInterface::class))
                )
                ->resolvedBy(function ($item, array $arguments, Context $context) {
                    return QueryType::ANIMALS;
                }),


            Field::withName('whoami')
                ->ofType(Type::string())
                ->resolvedBy(fn() => QueryType::WHOAMI_DATA),

            Field::withName('createAnimal')
                ->ofType(Type::string())
                ->withArguments(
                    InputField::withName('input')->ofType(CreateAnimalInputType::class)
                )->resolvedBy(
                    fn($data, array $arguments) => "Done: {$arguments['input']['id']}"
                ),

            Field::withName('user')
                ->ofType(new NonNull($registry->type(UserType::class)))
                ->resolvedBy(fn() => ['id' => QueryType::USER_ID]),

            Field::withName('jsonInput')
                ->ofType(JsonScalar::class)
                ->withArguments(
                    InputField::withName('json')
                        ->ofType(new NonNull($registry->type(JsonScalar::class)))
                )
                ->resolvedBy(
                    Middleware::create([])->then(function ($r, array $arguments) {
                        return ['json' => $arguments['json']];
                    })
                )
            ,

            Field::withName('animals')
                ->ofType(
                    Type::nonNull(Type::listOf(Type::nonNull($registry->type(AnimalUnion::class))))
                )
                ->resolvedBy(fn() => QueryType::ANIMALS)
            ,
        ];
    }

    protected function description(): string
    {
        return '';
    }
}
