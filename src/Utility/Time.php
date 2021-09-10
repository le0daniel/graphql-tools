<?php

declare(strict_types=1);


namespace GraphQlTools\Utility;


final class Time
{
    public static function nanoSeconds(): int {
        return hrtime(true);
    }
}
