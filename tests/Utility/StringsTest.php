<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Strings;
use PHPUnit\Framework\TestCase;

class StringsTest extends TestCase
{

    public function testPathToString()
    {
        self::assertEquals('brands.[].id', Strings::pathToString(['brands', 0, 'id']));
        self::assertEquals('brands.[].id', Strings::pathToString(['brands', 2, 'id']));
        self::assertEquals('brands.[].id', Strings::pathToString(['brands', 5, 'id']));
    }
}
