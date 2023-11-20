<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Classes;
use PHPUnit\Framework\TestCase;

class ClassesTest extends TestCase
{

    public function testGetDeclaredClassInFile()
    {
        self::assertEquals(self::class, Classes::getDeclaredClassInFile(__FILE__));
    }

}
