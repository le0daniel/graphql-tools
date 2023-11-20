<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Debugging
{
    /**
     * @internal
     * @param mixed $data
     * @return string
     */
    public static function typeOf(mixed $data): string
    {
        $type = gettype($data);
        return match (true) {
            is_string($data), is_numeric($data) => "{$type} ({$data})",
            is_array($data) => "{$type} (" . count($data) . ')',
            is_object($data) => "{$type} (" . get_class($data) . ')',
            is_bool($data) => "{$type} (" . ($data ? 'true' : 'false') . ')',
            default => $type
        };
    }

}