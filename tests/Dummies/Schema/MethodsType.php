<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Definition\Attributes\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\Traits\MethodsAsFields;

final class MethodsType extends GraphQlType
{
    use MethodsAsFields;

    protected function description(): string
    {
        return '';
    }

    #[Field(typeString: "String!")]
    public static function testField($data, array $args, GraphQlContext $context, ResolveInfo $info): string {
        return 'This is a testField';
    }

}