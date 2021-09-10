<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Arrays;
use PHPUnit\Framework\TestCase;

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

    /** @dataProvider keysExistProvider */
    public function testKeysExist(bool $expected, array $array, array $keys): void {
        self::assertEquals($expected, Arrays::keysExist($array, $keys));
    }

    public function keysExistProvider(): array {
        return [
            'simple case' => [
                true,
                ['key' => 'val', 'key2' => 'val'],
                ['key', 'key2']
            ],
            'invalid case' => [
                false,
                ['key' => 'val', 'key2' => 'val'],
                ['key', 'key3']
            ]
        ];
    }

    public function testOneKeyExists(): void
    {
        $array = ['key' => 'value', 'key2' => 'value'];

        self::assertTrue(Arrays::oneKeyExists($array, ['key']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key2']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key', 'key2']));
        self::assertFalse(Arrays::oneKeyExists($array, ['key3']));
    }

    public function testAppend(): void
    {
        $array = ['test'];
        self::assertEquals(['test', 'append'], Arrays::append($array, 'append'));
    }

    public function testMergeKeyValue(): void
    {
        $array = ['key' => 'value'];
        self::assertEquals(
            ['key' => 'value', 'key2' => 'value'],
            Arrays::mergeKeyValue(
                $array,
                [
                    'key2' => 'value',
                ]
            )
        );
    }

    public function testLast(){
        $array = ['one', 'two', 'three'];
        self::assertEquals('three', Arrays::last($array));
        self::assertEquals(['one', 'two', 'three'], $array);
    }
}
