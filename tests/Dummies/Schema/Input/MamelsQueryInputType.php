<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema\Input;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInputType;

class MamelsQueryInputType extends GraphQlInputType
{

    protected function description(): string
    {
        return 'My description';
    }

    protected function fields(): array
    {
        return [
            InputField::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->deprecated('my reason')
        ];
    }
}