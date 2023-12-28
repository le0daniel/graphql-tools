<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Arrays;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArraysTest extends TestCase
{

    public function testMapWithKeys() {
        self::assertEquals(
            ['zero' => 0, 'one' => 1, 'two' => 2],
            Arrays::mapWithKeys(
                ['zero', 'one', 'two'],
                fn($number, $text) => [$text, $number],
            )
        );
    }

    /** @dataProvider sortByColumnProvider */
    public function testSortByColumn(array $array, string $column, array $expected): void
    {
        self::assertEquals($expected, Arrays::sortByColumn($array, $column));
    }

    public function sortByColumnProvider(): array
    {
        return [
            'simple sort' => [
                [['key' => 'v'], ['key' => 'a']],
                'key',
                [['key' => 'a'], ['key' => 'v']],
            ],
            'nested simple sort' => [
                [['key' => ['key' => 'v']], ['key' => ['key' => 'a']]],
                'key.key',
                [['key' => ['key' => 'a']], ['key' => ['key' => 'v']]],
            ]
        ];
    }

    public function testMoveToPath(): void {
        self::assertEquals(null, Arrays::getByPathArray([], ['id', 4, 'data']));
        self::assertEquals('my-val', Arrays::getByPathArray(['id' => 'my-val'], ['id']));
        self::assertEquals(['type' => 'lion'], Arrays::getByPathArray([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion']
                ]
            ]
        ], ['data', 1, 'animal']));
        self::assertEquals(null, Arrays::getByPathArray([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion']
                ]
            ]
        ], ['data', '12', 'animal']));
        self::assertEquals(null, Arrays::getByPathArray([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion', 'null' => null]
                ]
            ]
        ], ['data', '1', 'animal', 'null'], 123));
        self::assertEquals(123, Arrays::getByPathArray([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion', 'null' => null]
                ]
            ]
        ], ['data', '1', 'animal', 'other'], 123));
    }


    public function testLast()
    {
        $array = ['one', 'two', 'three'];
        self::assertEquals('three', Arrays::last($array));
        self::assertEquals(['one', 'two', 'three'], $array);
    }
}
