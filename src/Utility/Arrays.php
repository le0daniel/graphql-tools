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

}
