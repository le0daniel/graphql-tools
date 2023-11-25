<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;


use Closure;
use RuntimeException;

final class Arrays
{
    public static function moveToPath(?array $data, array $path): mixed {
        foreach ($path as $part) {
            if (!is_array($data) || !isset($data[$part])) {
                return null;
            }

            $data = &$data[$part];
        }

        return $data;
    }

    /**
     * @internal
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

    /**
     * @internal
     * @param array $array
     * @param array $arrayToMerge
     * @param bool $throwOnKeyConflict
     * @return array
     */
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

    /**
     * @internal
     * @param mixed $value
     * @return bool
     */
    private static function isArrayAccessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * @internal
     * @param $array
     * @return array
     */
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

    /**
     * @internal
     * @param mixed $array
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
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

    /**
     * @internal
     * @param array $array
     * @param string $columnKey
     * @return array
     */
    public static function sortByColumn(array $array, string $columnKey): array
    {
        $columnsToSortBy = [];

        foreach ($array as $index => $row) {
            $columnsToSortBy[$index] = self::getValueByDotNotation($row, $columnKey);
        }

        array_multisort($columnsToSortBy, $array);

        return array_values($array);
    }

    /**
     * @internal
     * @param array $array
     * @return mixed
     */
    public static function last(array &$array): mixed
    {
        return $array[count($array) - 1];
    }

}
