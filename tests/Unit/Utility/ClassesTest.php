<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Classes;
use PHPUnit\Framework\TestCase;

class ClassesTest extends TestCase
{

    public function testIsClassName()
    {
        self::assertTrue(Classes::isClassName('\\Test\\MyClass'));
        self::assertTrue(Classes::isClassName(self::class));
        self::assertFalse(Classes::isClassName('Test'));
    }

    public function testGetDeclaredClassInFile()
    {
        self::assertEquals(self::class, Classes::getDeclaredClassInFile(__FILE__));
    }

    public function testBaseName() {
        self::assertEquals('ClassesTest', Classes::baseName(self::class));
    }

    public function testClassNameAsArray()
    {
        self::assertEquals([
            'GraphQlTools', 'Test', 'Unit', 'Utility', 'ClassesTest'
        ], Classes::classNameAsArray(self::class));

        self::assertEquals([
            'GraphQlTools', 'Test', 'Unit', 'Utility', 'ClassesTest'
        ], Classes::classNameAsArray('\\'.self::class));
    }
}
