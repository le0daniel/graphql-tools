<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\TypeRepository;

final class CurrentUserField extends GraphQlField
{

    protected function arguments(TypeRepository $repository): ?array
    {
        return [
            'name' => Type::string()
        ];
    }

    protected function fieldType(TypeRepository $repository): ScalarType
    {
        return Type::string();
    }

    protected function resolve(mixed $typeData, array $arguments, Context $context, ResolveInfo $info): string
    {
        if ($arguments['name'] ?? null) {
            return "Hello {$arguments['name']}";
        }

        return 'Hello World!';
    }

    protected function description(): ?string
    {
        return 'Type description';
    }
}