<?php declare(strict_types=1);

namespace GraphQlTools\Test\Definition\Field;

use DateTime;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Helper\TypeRegistry;
use PHPUnit\Framework\TestCase;

class InputFieldTest extends TestCase
{

    public function testToInputFieldDefinitionArray()
    {
        self::assertIsArray(
            InputField::withName('test')
                ->ofType(Type::id())
                ->toDefinition(new TypeRegistry([]))
        );
    }

    public function testToInputFieldDefinitionForDeprecatedItem()
    {
        self::assertTrue(
            InputField::withName('test')
                ->ofType(Type::id())
                ->deprecated('', new DateTime('2020-10-10'), true)
                ->isHidden(new TypeRegistry([]))
        );
    }
}
