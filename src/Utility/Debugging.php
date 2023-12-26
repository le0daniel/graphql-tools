<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use Throwable;

final class Debugging
{
    /**
     * @internal
     * @param mixed $data
     * @return string
     */
    public static function typeOf(mixed $data): string
    {
        if (is_object($data)) {
            $className = get_class($data);
            return match (true) {
                $data instanceof Throwable => "throwable ({$className})",
                $data instanceof Closure => 'closure',
                default => "object ({$className})",
            };
        }

        $type = gettype($data);
        $context = match (true) {
            is_string($data), is_numeric($data) => $data,
            is_array($data) => count($data),
            is_bool($data) => $data ? 'true' : 'false',
            default => null
        };

        return is_null($context)
            ? $type
            : "{$type} ($context)";
    }

    public static function implodeOptions(array $options): string {
        return implode(', ', array_map(fn(string $option): string => "'{$option}'", $options));
    }

}