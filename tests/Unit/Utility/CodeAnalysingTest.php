<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\CodeAnalysing;
use PHPUnit\Framework\TestCase;

class CodeAnalysingTest extends TestCase
{

    /**
     * @param array $expected
     * @param string $code
     * @return void
     * @dataProvider selfAndStaticUsagesDataProvider
     */
    public function testSelfAndStaticUsages(array $expected, string $code): void
    {
        self::assertEquals($expected, CodeAnalysing::selfAndStaticUsages($code));
    }

    protected function selfAndStaticUsagesDataProvider(): array {
        return [
            'With self and static' => [
                ['self::$MY_VARIABLE', 'static::STRING'],
                'function (string $input) {
                    return self::$MY_VARIABLE . static::STRING . $input . "self::something";
                }'
            ],
            'With self function call' => [
                ['self::method', 'static::STRING'],
                'function (string $input) {
                    return self::method() . static::STRING . $input . "self::something";
                }'
            ],
            'With static variable' => [
                ['static::$MY_VARIABLE', 'static::class'],
                'function (string $input) {
                    return static::$MY_VARIABLE . static::class . $input . "self::something";
                }'
            ],
        ];
    }
}
