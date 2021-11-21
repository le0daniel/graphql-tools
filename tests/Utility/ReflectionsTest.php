<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\LazyRepository;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Reflections;
use PHPUnit\Framework\TestCase;

class ReflectionsTest extends TestCase
{

    public function testGetAllParentClasses()
    {
        self::assertEquals([
            TypeRepository::class
        ], Reflections::getAllParentClasses(new \ReflectionClass(LazyRepository::class)));
    }
}
