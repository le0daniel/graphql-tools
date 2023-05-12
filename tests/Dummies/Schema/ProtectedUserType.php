<?php declare(strict_types=1);

namespace GraphQlTools\Test\Dummies\Schema;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;

final class ProtectedUserType extends GraphQlType
{

    protected function middleware(): array|null
    {
        return [
            fn($data, $args, $context, $info, \Closure $next) => 'not allowed',
        ];
    }

    protected function fields(TypeRegistry $registry): array
    {
        return [
            Field::withName('secret')
                ->ofType(Type::string())
                ->resolvedBy(fn() => 'secret value')
        ];
    }

    protected function description(): string
    {
        return '';
    }
}