<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Type\Definition\Type;

final class Types
{

    public static function enforceTypeLoading(Type|callable $type): Type
    {
        return $type instanceof Type
            ? $type
            : $type();
    }

}