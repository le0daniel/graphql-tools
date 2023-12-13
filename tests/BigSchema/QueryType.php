<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\BigSchema\Types\ClubType;

final class QueryType extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('clubs')
                ->ofType(new ListOfType($registry->type(ClubType::class)))
                ->arguments(
                    InputField::withName('page')
                        ->ofType(new NonNull($registry->int()))
                        ->withDefaultValue(1),
                    InputField::withName('limit')
                        ->ofType(new NonNull($registry->int()))
                        ->withDefaultValue(5),
                )
                ->resolvedBy(static function($_, $args, BigSchemaContext $context) {
                    return $context->loadClubs($args);
                }),

            Field::withName('club')
                ->ofType($registry->type(ClubType::class))
                ->withArguments(
                    InputField::withName('id')
                        ->ofType(new NonNull($registry->id()))
                )
                ->resolvedBy(
                    fn($_, $args, BigSchemaContext $context) => $context->dataLoader('clubsById')->load($args['id'])
                )
        ];
    }

}