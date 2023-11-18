<?php declare(strict_types=1);

namespace GraphQlTools\Utility\Middleware;

use ArrayAccess;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Helper\Resolver\ProxyResolver;

final class Federation
{
    private static function extractKey(string $key, mixed $objectOrArrayAccessible): mixed {
        if (null === $objectOrArrayAccessible) {
            return null;
        }

        if (is_array($objectOrArrayAccessible) || $objectOrArrayAccessible instanceof ArrayAccess) {
            return $objectOrArrayAccessible[$key] ?? null;
        }

        if (!is_object($objectOrArrayAccessible)) {
            return null;
        }

        /** @var object $objectOrArrayAccessible */
        return match (true) {
            isset($objectOrArrayAccessible->{$key}) => $objectOrArrayAccessible->{$key},
            method_exists($objectOrArrayAccessible, $key) => $objectOrArrayAccessible->{$key}(),
            default => null,
        };
    }

    public static function key(string $name): Closure {
        return static function(mixed $data, $args, $context, $info, Closure $next) use ($name) {
            return $next(
                self::extractKey($name, $data),
                $args,
                $context,
                $info
            );
        };
    }

    public static function field(string $name): Closure {
        return static function(mixed $data, $args, $context, ResolveInfo $info, Closure $next) use ($name) {
            /** @var ProxyResolver $resolveFunction */
            $resolveFunction = $info->parentType->getField($name)->resolveFn;
            return $next(
                // This does not execute the extensions. It only executes middlewares and the resolve function.
                $resolveFunction->resolveToValue($data, $args, $context, $info),
                $args,
                $context,
                $info
            );
        };
    }
}