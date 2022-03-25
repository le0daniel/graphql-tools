<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


final class Time
{
    public static function nanoSeconds(): int {
        return hrtime(true);
    }

    public static function nanoSecondsToSeconds(int $nanoSeconds): float {
        return $nanoSeconds / (1000 * 1000 * 1000);
    }
}
