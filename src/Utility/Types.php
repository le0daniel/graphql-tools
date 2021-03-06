<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use GraphQL\Type\Definition\Type;

final class Types
{
    public static function enforceTypeLoading(Type|Closure $type): Type
    {
        return $type instanceof Type
            ? $type
            : $type();
    }
}