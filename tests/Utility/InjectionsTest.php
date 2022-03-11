<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Data\Models\Holder;
use GraphQlTools\Test\Dummies\HolderDummy;
use GraphQlTools\Utility\Injections;
use PHPUnit\Framework\TestCase;

class InjectionsTest extends TestCase
{
    public const CALLABLE_RETURN_VALUE = 'result';

    /** @dataProvider withPositionalArgumentsDataProvider */
    public function testWithPositionalArguments(callable $callable, array $positionalArguments, callable $createInstanceOfClass)
    {
        self::assertEquals(self::CALLABLE_RETURN_VALUE, Injections::withPositionalArguments(
            $callable,
            $positionalArguments,
            $createInstanceOfClass
        ));
    }

    public function withPositionalArgumentsDataProvider(): array {
        return [
            'Callable without any arguments' => [
                fn() => self::CALLABLE_RETURN_VALUE,
                [],
                fn() => null
            ],
            'Callable with positional Argument' => [
                fn(int $number) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => null
            ],
            'Callable with positional Argument and injection' => [
                fn(int $number, Holder $holder) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => HolderDummy::create([])
            ],
            'Callable with nullable injection' => [
                fn(int $number, ?Holder $holder) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => null
            ],
            'Callable with nullable primitive' => [
                fn(int $number, ?int $holder) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => null
            ],
            'Callable with primitive and default value' => [
                fn(int $number, int $holder = 1) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => null
            ],
            'Callable with untyped primitive' => [
                fn(int $number, $holder) => self::CALLABLE_RETURN_VALUE,
                [1],
                fn() => null
            ],
            'Callable with untyped primitive and default value' => [
                fn(int $number, $holder = self::CALLABLE_RETURN_VALUE) => $holder,
                [1],
                fn() => null
            ]
        ];
    }
}
