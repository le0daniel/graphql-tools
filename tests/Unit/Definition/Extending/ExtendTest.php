<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Definition\Extending;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\Extend;
use GraphQlTools\Definition\Field\Field;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ExtendTest extends TestCase
{
    use ProphecyTrait;

    public function testInterface()
    {
        $extendedType = Extend::interface('MyTypeName');
        self::assertEquals('MyTypeName', $extendedType->typeName());
    }

    public function testType()
    {
        $extendedType = Extend::type('MyTypeName');
        self::assertEquals('MyTypeName', $extendedType->typeName());
    }

    public function testGetFields()
    {
        $field = Field::withName('test');
        $extendedType = Extend::type('MyTypeName')->withFields(fn() => [$field]);
        self::assertSame(
            $field,
            $extendedType->getFields($this->prophesize(TypeRegistry::class)->reveal())[0]
        );
    }

    public function testApplyMiddleware()
    {
        $field = Field::withName('test');
        $fields = Extend::type('MyTypeName')
            ->withFields(fn() => [$field])
            ->applyMiddleware(fn() => null)
            ->getFields($this->prophesize(TypeRegistry::class)->reveal());

        self::assertNotSame(
            $field, $fields[0]
        );

        self::assertEquals(
            $field->name, $fields[0]->name
        );
    }
}
