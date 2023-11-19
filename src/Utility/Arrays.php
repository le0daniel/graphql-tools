<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;


use Closure;
use RuntimeException;

final class Arrays
{
    /**
     * @template K of string|int
     * @template V
     * @param iterable<mixed, mixed> $array
     * @param Closure $closure
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

}
