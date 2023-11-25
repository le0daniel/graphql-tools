<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Arrays;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArraysTest extends TestCase
{

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
        self::assertEquals(null, Arrays::moveToPath(null, ['id', 4, 'data']));
        self::assertEquals('my-val', Arrays::moveToPath(['id' => 'my-val'], ['id']));
        self::assertEquals(['type' => 'lion'], Arrays::moveToPath([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion']
                ]
            ]
        ], ['data', 1, 'animal']));
        self::assertEquals(null, Arrays::moveToPath([
            'data' => [
                0 => [],
                1 => [
                    'animal' => ['type' => 'lion']
                ]
            ]
        ], ['data', '12', 'animal']));
    }


    public function testLast()
    {
        $array = ['one', 'two', 'three'];
        self::assertEquals('three', Arrays::last($array));
        self::assertEquals(['one', 'two', 'three'], $array);
    }
}
