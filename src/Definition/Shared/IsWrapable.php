<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\WrappedType;

trait IsWrapable {

    public static function wrap(\Closure $resolve): WrappedType {
        return new WrappedType(static::class, $resolve);
    }

}
