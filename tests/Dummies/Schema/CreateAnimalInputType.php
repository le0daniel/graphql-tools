<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlInputType;

class CreateAnimalInputType extends GraphQlInputType
{

    protected function fields(TypeRegistry $registry): array
    {
        return [
            InputField::withName('id')
                ->ofType($registry->nonNull($registry->id()))
                ->withDefaultValue('My-ID')
        ];
    }

    protected function description(): string
    {
        return 'Test Input Field';
    }
}