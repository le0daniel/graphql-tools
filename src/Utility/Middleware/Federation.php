<?php declare(strict_types=1);

namespace GraphQlTools\Utility\Middleware;

use ArrayAccess;
use Closure;
use RuntimeException;

final class Federation
{
    public static function key(string $name): Closure {
        return static function(mixed $data, $args, $context, $info, Closure $next) use ($name) {
            if (null === $data) {
                return $next(null, $args, $context, $info);
            }

            if ($data instanceof ArrayAccess || is_array($data)) {
                if (!isset($data[$name])) {
                    return new RuntimeException("Could not resolve federated key `{$name}` on array|ArrayAccessible. Hint: Federation::key(name) requires array|ArrayAccessible to have a value set for the key `{$name}`.");
                }

                return $next($data[$name], $args, $context, $info);
            }

            if (!is_object($data)) {
                $type = gettype($data);
                return new RuntimeException("Could not resolve federated key `{$name}` on {$type}. Hint: Federation::key(name) requires the data to be an array (& ArrayAccessible) or an object with properties or getter methods");
            }

            $typeClass = $data::class;
            return match (true) {
                isset($data->{$name}) => $next($data->{$name}, $args, $context, $info),
                method_exists($data, $name) => $next($data->{$name}(), $args, $context, $info),
                default => new RuntimeException("Could not resolve federated key `{$name}` on {$typeClass}. Hint: Federation::key(name) requires the data to be an array (& ArrayAccessible) or an object with properties or getter methods")
            };
        };
    }
}