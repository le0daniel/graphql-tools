<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Data\Models;

final class NamedDummy
{

    public function __construct(
        public readonly string $test,
        public readonly string $variable
    )
    {
    }
}