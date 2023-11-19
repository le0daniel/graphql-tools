<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Utility\Debugging;
use PHPUnit\Framework\TestCase;

class DebuggingTest extends TestCase
{

    public function testTypeOf()
    {
        self::assertEquals("integer (10)", Debugging::typeOf(10));
        self::assertEquals("boolean (true)", Debugging::typeOf(true));
        self::assertEquals("boolean (false)", Debugging::typeOf(false));
        self::assertEquals("double (10.2)", Debugging::typeOf(10.2));
        self::assertEquals("array (2)", Debugging::typeOf([1, 2]));
        self::assertEquals("string (GraphQlTools\Utility\Debugging)", Debugging::typeOf(Debugging::class));
        self::assertEquals("object (GraphQlTools\Utility\Debugging)", Debugging::typeOf(new Debugging()));
    }
}
