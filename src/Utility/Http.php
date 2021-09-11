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

}