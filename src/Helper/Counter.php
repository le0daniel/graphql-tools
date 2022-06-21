<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

final class Counter
{
    private int $count = 0;

    public function increase(): void
    {
        $this->count++;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}