<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Utility\Classes;

abstract class GraphQlScalar extends ScalarType
{
    use HasDescription;

    private const CLASS_POSTFIX = 'Scalar';

    final public function __construct()
    {
        parent::__construct(
            [
                'description' => $this->description(),
                'name' => static::typeName()
            ]
        );
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }
}
