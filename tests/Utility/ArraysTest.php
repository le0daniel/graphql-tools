<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Arrays;
use PHPUnit\Framework\TestCase;

final class ArraysTest extends TestCase {

    public function testOneKeyExists(): void{
        $array = ['key' => 'value', 'key2' => 'value'];

        self::assertTrue(Arrays::oneKeyExists($array, ['key']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key2']));
        self::assertTrue(Arrays::oneKeyExists($array, ['key', 'key2']));
        self::assertFalse(Arrays::oneKeyExists($array, ['key3']));
    }

    public function testAppend(): void{
        $array = ['test'];
        self::assertEquals(['test', 'append'], Arrays::append($array, 'append'));
    }

    public function testMergeKeyValue(): void{
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
}
