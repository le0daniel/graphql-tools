<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Http
{

    public static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $headerName = self::normalizeHeaderName($key);
            $normalized[$headerName] = trim($value);
        }
        return $normalized;
    }

    public static function normalizeHeaderName(string $name): string
    {
        return trim(strtolower($name));
    }

    public static function headerValues(string $headerValue): array
    {
        return array_map(static fn(string $value): string => trim($value), explode(',', $headerValue));
    }

}