<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Arrays
{

    public static function mergeKeyValue(array $array, array $arrayToPush): array
    {
        foreach ($arrayToPush as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

    public static function append(array $array, mixed $append): array
    {
        $array[] = $append;
        return $array;
    }

    public static function oneKeyExists(array $array, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return true;
            }
        }
        return false;
    }

    public static function keysExist(array $array, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    private static function isArrayAccessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    private static function getValueByDotNotation(mixed $array, string $key, mixed $defaultValue = null): mixed
    {
        $keyParts = explode('.', $key);
        $value = $array;

        foreach ($keyParts as $keyPart) {
            $value = self::isArrayAccessible($value) ? ($value[$keyPart] ?? null) : ($value->{$keyPart} ?? null);
            if ($value === null) {
                return $defaultValue;
            }
        }

        return $value ?? $defaultValue;
    }

    public static function splice(array $array, int $offset, ?int $length): array
    {
        return array_splice($array, $offset, $length);
    }

    public static function blacklistKeys(array $array, array $blacklist, bool $recursive = true): array {
        $filtered = [];
        foreach ($array as $key => $value) {
            if (in_array($key, $blacklist)) {
                continue;
            }

            $filtered[$key] = $recursive && is_array($value)
                ? self::blacklistKeys($value, $blacklist, $recursive)
                : $value;
        }

        return $filtered;
    }

    public static function sortByColumn(array $array, string $columnKey): array
    {
        $columnsToSortBy = [];

        foreach ($array as $index => $row) {
            $columnsToSortBy[$index] = self::getValueByDotNotation($row, $columnKey);
        }

        array_multisort($columnsToSortBy, $array);

        return array_values($array);
    }

    public static function last(array $array): mixed {
        return array_pop($array);
    }

    public static function removeNullValues(array $array): array {
        return array_filter($array, fn($value): bool => $value !== null);
    }

}
