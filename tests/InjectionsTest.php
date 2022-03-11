<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\Utility\Injections;
use PHPUnit\Framework\TestCase;

class InjectionsTest extends TestCase
{
    public static function dummyStaticMethod(int $number){
        return $number;
    }

    public function dummyMethod(int $number){
        return $number;
    }

    public function testWithPositionalArguments()
    {
        self::assertEquals('yes', Injections::withPositionalArguments(fn() => 'yes', [], fn() => null));
        self::assertEquals('yes', Injections::withPositionalArguments(fn() => 'yes', [], fn() => null));
        self::assertEquals('yes', Injections::withPositionalArguments(fn(int $number) => 'yes', [1], fn() => null));
        self::assertEquals('yes', Injections::withPositionalArguments(fn(int $number, string $string) => 'yes', [1, ''], fn() => null));

        self::assertEquals(1, Injections::withPositionalArguments(
            [self::class, 'dummyStaticMethod'],
            [1],
            fn() => null,
        ));
        self::assertEquals(1, Injections::withPositionalArguments(
            '\GraphQlTools\Test\InjectionsTest::dummyStaticMethod',
            [1],
            fn() => null,
        ));
        self::assertEquals(1, Injections::withPositionalArguments(
            $this->dummyMethod(...),
            [1],
            fn() => null,
        ));
        self::assertEquals(1, Injections::withPositionalArguments(
            [new self(), 'dummyMethod'],
            [1],
            fn() => null,
        ));
        self::assertEquals('', Injections::withPositionalArguments(
            fn(int $number, ResolveInfoDummy $type) => '',
            [1],
            fn(string $className) => new $className,
        ));
    }
}
