<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\Context;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Test\Dummies\Schema\Input\MamelsQueryInputType;
use GraphQlTools\Utility\Middleware\Federation;

final class QueryType extends GraphQlType
{

    protected function lazyFields(): bool
    {
        return false;
    }

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
                ->ofType($registry->string())
                ->withArguments(
                    InputField::withName('name')
                        ->tags('private')
                        ->ofType($registry->string())
                        ->deprecated('Some reasons')
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

            Field::withName('isHiddenDynamically')
                ->ofType($registry->string())
                ->visible(fn(): bool => false)
                ->resolvedBy(fn() => 'I am visible'),

            Field::withName('middlewareWithoutResolver')
                ->ofType($registry->string())
                ->middleware(fn($a, $b, $c, $d, $next) => ($next(['middlewareWithoutResolver' => 'Default'], $b, $c, $d)). ' - this is the middle'),

            Field::withName('testFieldMiddleware')
                ->ofType($registry->nonNull($registry->string()))
                ->middleware(Federation::field('currentUser'))
                ->resolvedBy(fn(string $data): string => $data),

            Field::withName('middlewareWithPrimitiveBinding')
                ->ofType($registry->string())
                ->tags('private')
                ->resolvedBy(static function($_, $__, $context, ResolveInfo $info) use ($value) {
                    return $value;
                }),

            Field::withName('mamelsQuery')
                ->ofType($registry->string())
                ->withArguments(
                    InputField::withName('query')
                        ->ofType($registry->type(MamelsQueryInputType::class))
                )
                ->resolvedBy(fn($data, array $args) => 'My result: ' . ($args['query']['name'] ?? '-- no query --')),

            Field::withName('mamels')
                ->ofType(
                    new ListOfType($registry->type(MamelInterface::class))
                )
                ->resolvedBy(function ($item, array $arguments, Context $context) {
                    return QueryType::ANIMALS;
                }),

            Field::withName('protectedUser')
                ->ofType($registry->type('ProtectedUser'))
                ->resolvedBy(fn() => 'some data'),

            Field::withName('whoami')
                ->ofType($registry->string())
                ->resolvedBy(fn() => QueryType::WHOAMI_DATA),

            Field::withName('createAnimal')
                ->ofType($registry->string())
                ->withArguments(
                    InputField::withName('input')
                        ->ofType($registry->type(CreateAnimalInputType::class))
                )->resolvedBy(
                    fn($data, array $arguments) => "Done: {$arguments['input']['id']}"
                ),

            Field::withName('user')
                ->ofType($registry->nonNull($registry->type(UserType::class)))
                ->resolvedBy(fn() => ['id' => QueryType::USER_ID]),

            Field::withName('jsonInput')
                ->ofType($registry->type(JsonScalar::class))
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
                    $registry->nonNull(
                        $registry->listOf(
                            $registry->nonNull(
                                $registry->type(AnimalUnion::class)
                            )
                        )
                    )
                )
                ->resolvedBy(fn() => QueryType::ANIMALS),

            Field::withName('overwritten')
                ->ofType($registry->type(TypeWithOtherNamePattern::class))
                ->resolvedBy(fn() => new \stdClass()),

            Field::withName('overwrittenAlias')
                ->ofType($registry->type('OverwrittenName'))
                ->resolvedBy(fn() => new \stdClass()),
        ];
    }

    protected function description(): string
    {
        return '';
    }
}
