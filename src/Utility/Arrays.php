<?php

declare(strict_types=1);

namespace GraphQlTools\Utility;

final class Arrays {

    public static function mergeKeyValue(array $array, array $arrayToPush): array {
        foreach ($arrayToPush as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

    public static function append(array $array, mixed $append): array {
        $array[] = $append;
        return $array;
    }

    public static function oneKeyExists(array $array, array $keys): bool {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return true;
            }
        }
        return false;
    }

    private static function isArrayAccessible(mixed $value): bool {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    private static function getValueFromArray(mixed $array, string $key, mixed $defaultValue = null): mixed {
        $keyParts = explode('.', $key);
        $value = $array;

        foreach ($keyParts as $keyPart) {
            $value = self::isArrayAccessible($value) ? ($value[$keyPart] ?? null) : ($value->{$keyPart} ?? null);
            if (!$value) {
                return $defaultValue;
            }
        }

        return $value;
    }

    public static function sortByColumn(array $array, string $columnKey) {
        $sortedArray = [];
        $columnsToSortBy = [];

        foreach ($array as $index => $row) {
            $columnsToSortBy[$index] = self::getValueFromArray($row, $columnKey);
        }

        array_multisort($columnsToSortBy, $array);

        foreach ($columnsToSortBy as $index => $value) {
            $sortedArray[] = $array[$index];
        }

        return array_values($array);
    }

}
