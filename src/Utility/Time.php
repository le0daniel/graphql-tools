<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


final class Time
{
    private const DEFAULT_PRECISION = 6;

    public static function nanoSeconds(): int
    {
        return hrtime(true);
    }

    public static function nanoSecondsToSeconds(int $nanoSeconds, int $precision = self::DEFAULT_PRECISION): float
    {
        return round($nanoSeconds / (1000 * 1000 * 1000), $precision);
    }
}
