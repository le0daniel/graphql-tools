<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;


use Closure;
use RuntimeException;

final class Arrays
{

    public static function allKeysExist(array $array, array $requiredKeys): bool
    {
        $keyIntersection = array_intersect_key(array_flip($requiredKeys), $array);
        return count($requiredKeys) === count($keyIntersection);
    }

    /**
     * @template K of string|int
     * @template V
     * @param iterable<mixed, mixed> $array
     * @param Closure(mixed $key, mixed $value) $closure
     * @return array<K, V>
     */
    public static function mapWithKeys(iterable $array, Closure $closure): array
    {
        $items = [];
        foreach ($array as $key => $value) {
            [$newKey, $newValue] = $closure($key, $value);
            $items[$newKey] = $newValue;
        }
        return $items;
    }

    public static function containsOneOf(array $array, array $values): bool
    {
        foreach ($values as $value) {
            if (in_array($value, $array, true)) {
                return true;
            }
        }
        return false;
    }

    public static function mergeKeyValues(array $array, array $arrayToMerge, bool $throwOnKeyConflict = false): array
    {
        foreach ($arrayToMerge as $key => $value) {
            if ($throwOnKeyConflict && array_key_exists($key, $array)) {
                throw new RuntimeException("The key '{$key}' does already exist in the array to merge into.");
            }
            $array[$key] = $value;
        }
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

    public static function onlyKeys(array $array, array $keys, bool $throwOnNonExistentKey = true): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array) && $throwOnNonExistentKey) {
                $gottenArrayKeys = implode(', ', array_keys($array));
                $expectedArrayKeys = implode(', ', $keys);
                throw new RuntimeException("Not all required keys were set. Got: {$gottenArrayKeys}. Expected: {$expectedArrayKeys}");
            }

            $result[$key] = $array[$key] ?? null;
        }
        return $result;
    }

    private static function isArrayAccessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    public static function nonRecursiveFlatten(&$array): array
    {
        $flattened = [];
        foreach ($array as $potentialArray) {
            $valueToPush = is_array($potentialArray)
                ? array_values($potentialArray)
                : [$potentialArray];
            array_push($flattened, ...$valueToPush);
        }
        return $flattened;
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

    public static function blacklistKeys(array $array, array $blacklist, bool $recursive = true): array
    {
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

    public static function last(array &$array): mixed
    {
        return $array[count($array) - 1];
    }

    public static function removeNullValues(array $array): array
    {
        return array_filter($array, static fn(mixed $value): bool => $value !== null);
    }

}
