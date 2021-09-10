<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\SideEffects;
use PHPUnit\Framework\TestCase;

class SideEffectsTest extends TestCase
{

    public function testTap()
    {
        $testValue = 'my string';

        $result = SideEffects::tap($testValue, static function(string $value) use ($testValue) {
            self::assertEquals($testValue,  $value);
            return 'other value';
        });

        self::assertEquals($testValue, $result);
    }
}
