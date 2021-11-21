<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Middlewares;
use PHPUnit\Framework\TestCase;

class MiddlewaresTest extends TestCase
{

    public function testExecuteAndReturnNext()
    {
        $middlewares = [
            fn() => fn($value) => "First: {$value}",
            fn() => null,
            fn() => fn($value) => "Second: {$value}",
        ];

        $goOut = Middlewares::executeAndReturnNext($middlewares, static fn($enter) => $enter());
        self::assertEquals('First: Second: Value', $goOut('Value'));
    }
}
