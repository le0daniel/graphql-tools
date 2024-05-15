<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Utility\Paths;
use PHPUnit\Framework\TestCase;

class PathsTest extends TestCase
{

    public function testToNormalizedString()
    {
        self::assertEquals('a.[].b.c', Paths::toNormalizedString(['a', 3, 'b', 'c']));
        self::assertEquals('a.b.c', Paths::toNormalizedString(['a', 'b', 'c']));
    }

    public function testToString()
    {
        self::assertEquals('a.b.c', Paths::toString(['a', 'b', 'c']));
    }
}
