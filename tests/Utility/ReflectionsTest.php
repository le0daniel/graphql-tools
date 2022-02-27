<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Data\Models\Holder;
use GraphQlTools\Test\Dummies\HolderDummy;
use GraphQlTools\Utility\Reflections;
use PHPUnit\Framework\TestCase;

class ReflectionsTest extends TestCase
{

    public function testGetAllParentClasses()
    {
        self::assertEquals([
            Holder::class
        ], Reflections::getAllParentClasses(new \ReflectionClass(HolderDummy::class)));
    }
}
