<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\SchemaForDataLoader;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Context;

class QueryType extends GraphQlType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('loadByIds')
                ->ofType(new ListOfType($registry->type(IngredientType::class)))
                ->withArguments(
                    InputField::withName('ids')
                        ->ofType(new NonNull(new ListOfType(new NonNull($registry->id()))))
                )
                ->resolvedBy(fn($_, $arguments, Context $context) => $context
                    ->dataLoader('loadMany')
                    ->loadMany(... $arguments['ids'])
                )
        ];
    }

    protected function description(): string
    {
        return 'No description';
    }
}