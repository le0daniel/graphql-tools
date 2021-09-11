<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Http
{

    public static function normalizeHeaders(array $headers): array {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[trim(strtolower($key))] = trim($value);
        }
        return $normalized;
    }

    public static function headerValues(string $headerValue): array {
        return array_map(fn(string $value): string => trim($value), explode(',', $headerValue));
    }

}