<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInputType;

class CreateAnimalInputType extends GraphQlInputType
{

    protected function fields(): array
    {
        return [
            InputField::withName('id')
                ->ofType(Type::id())
                ->withDefaultValue('My-ID')
        ];
    }

    protected function description(): string
    {
        return 'Test Input Field';
    }
}