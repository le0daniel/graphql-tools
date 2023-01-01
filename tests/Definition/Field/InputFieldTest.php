<?php declare(strict_types=1);

namespace GraphQlTools\Test\Definition\Field;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\InputField;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class InputFieldTest extends TestCase
{
    use ProphecyTrait;

    public function testToInputFieldDefinitionArray()
    {
        self::assertIsArray(
            InputField::withName('test')
                ->ofType(Type::id())
                ->toDefinition($this->prophesize(TypeRegistry::class)->reveal())
        );
    }
}
