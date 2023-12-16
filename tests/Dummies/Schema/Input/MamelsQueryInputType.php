<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Input;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInputType;

class MamelsQueryInputType extends GraphQlInputType
{

    protected function description(): string
    {
        return 'My description';
    }

    protected function fields(TypeRegistry $registry): array
    {
        return [
            InputField::withName('name')
                ->ofType(new NonNull($registry->string()))
                ->deprecated('my reason')
        ];
    }
}