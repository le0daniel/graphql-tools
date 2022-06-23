<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Arrays;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArraysTest extends TestCase
{
    public function testAllKeysExists(): void {
        self::assertTrue(Arrays::allKeysExist([], []));
        self::assertTrue(Arrays::allKeysExist(['test' => 1, 'test2' => true], ['test']));
        self::assertTrue(Arrays::allKeysExist(['test' => 1, 'test2' => true], ['test', 'test2']));
        self::assertTrue(Arrays::allKeysExist(['test' => 1, 'test2' => true], []));

        self::assertFalse(Arrays::allKeysExist(['test' => 1, 'test2' => true], ['test3']));
        self::assertFalse(Arrays::allKeysExist([], ['test3']));
    }

    public function testContainsOneOf(){
        $testArray = ['one', 'two', 'three', 1, 2, 3];
        self::assertTrue(Arrays::containsOneOf($testArray, [1, 'two']));
        self::assertTrue(Arrays::containsOneOf($testArray, [1, '1two']));
        self::assertTrue(Arrays::containsOneOf($testArray, [12, 'two']));
        self::assertFalse(Arrays::containsOneOf($testArray, [12, '1']));
        self::assertFalse(Arrays::containsOneOf($testArray, [12, '2']));
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

    /** @dataProvider keysExistProvider */
    public function testKeysExist(bool $expected, array $array, array $keys): void
    {
        if (!$expected) {
            $this->expectException(RuntimeException::class);

            $gottenArrayKeys = implode(', ', array_keys($array));
            $expectedArrayKeys = implode(', ', $keys);
            $this->expectExceptionMessage(
                "Not all required keys were set. Got: {$gottenArrayKeys}. Expected: {$expectedArrayKeys}"
            );
        }

        self::assertEquals($keys, array_keys(Arrays::onlyKeys($array, $keys, true)));
    }

    public function keysExistProvider(): array
    {
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

    public function testRemoveNullValues() {
        self::assertEquals(['key' => 0, 3 => 'test'], Arrays::removeNullValues(['key' => 0, 0 => null, 'value' => null, 3 => 'test']));
    }

    public function testOneKeyExists(): void
    {
        $array = ['key' => 'value', 'key2' => 'value'];

        self::assertTrue(Arrays::oneKeyExists($array, ['key']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key2']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key', 'key2']));
        self::assertFalse(Arrays::oneKeyExists($array, ['key3']));
    }

    public function testLast()
    {
        $array = ['one', 'two', 'three'];
        self::assertEquals('three', Arrays::last($array));
        self::assertEquals(['one', 'two', 'three'], $array);
    }

    public function testBlacklistKeys(): void
    {
        self::assertEquals(['key' => 'value'], Arrays::blacklistKeys(['key' => 'value', 'secret' => 1], ['secret']));
        self::assertEquals(['key' => 'value', 'test' => []], Arrays::blacklistKeys(['key' => 'value', 'test' => ['secret' => true], 'secret' => 1], ['secret']));
    }
}
