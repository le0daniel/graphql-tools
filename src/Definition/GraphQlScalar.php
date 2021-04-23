<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\IsWrapable;
use GraphQlTools\Utility\Strings;

abstract class GraphQlScalar extends ScalarType {
    use HasDescription, IsWrapable;

    public function __construct() {
        parent::__construct(
            [
                'description' => $this->description(),
                'name' => static::typeName()
            ]
        );
    }

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Scalar')
            ? substr($typeName, 0, -strlen('Scalar'))
            : $typeName;
    }
}
