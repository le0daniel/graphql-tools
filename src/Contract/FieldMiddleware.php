<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

interface FieldMiddleware
{
    /**
     * Return a function or null that warps the Resolver
     * @param array $arguments
     * @return Closure(mixed, array, GraphQlContext, ResolveInfo, Closure): mixed|null
     */
    public static function createMiddleware(array $arguments): ?Closure;
}