<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Paths;
use PHPUnit\Framework\TestCase;

class PathsTest extends TestCase
{

    public function testToString()
    {
        self::assertEquals('brands.[].id', Paths::toString(['brands', 0, 'id']));
        self::assertEquals('brands.[].id', Paths::toString(['brands', 2, 'id']));
        self::assertEquals('brands.[].id', Paths::toString(['brands', 5, 'id']));
    }
}
