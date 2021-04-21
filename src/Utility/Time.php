<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


final class Time
{
    private const SECONDS_TO_NANOSECONDS_MULTIPLIER = 1000 * 1000 * 1000;

    public static function nanoSeconds(): int {
        return function_exists('hrtime')
            ? (int) hrtime(true)
            : (int) microtime(true) * self::SECONDS_TO_NANOSECONDS_MULTIPLIER;
    }

}
