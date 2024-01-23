<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlDirective;
use GraphQlTools\Test\Dummies\Schema\AnimalUnion;
use GraphQlTools\Test\Dummies\Schema\CreateAnimalInputType;
use GraphQlTools\Test\Dummies\Schema\Directives\ExportDirective;
use GraphQlTools\Test\Dummies\Schema\EatingEnum;
use GraphQlTools\Test\Dummies\Schema\JsonScalar;
use GraphQlTools\Test\Dummies\Schema\MamelInterface;
use GraphQlTools\Test\Dummies\Schema\TigerType;
use GraphQlTools\Utility\Types;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TypesTest extends TestCase
{
    use ProphecyTrait;

    public function testInferNameFromClassName()
    {
        self::assertEquals('Animal', Types::inferNameFromClassName(AnimalUnion::class));
        self::assertEquals('Eating', Types::inferNameFromClassName(EatingEnum::class));
        self::assertEquals('Mamel', Types::inferNameFromClassName(MamelInterface::class));
        self::assertEquals('Tiger', Types::inferNameFromClassName(TigerType::class));
        self::assertEquals('Json', Types::inferNameFromClassName(JsonScalar::class));
        self::assertEquals('CreateAnimalInput', Types::inferNameFromClassName(CreateAnimalInputType::class));
        self::assertEquals('export', Types::inferNameFromClassName(ExportDirective::class));
    }

    public function testInferExtensionTypeName(): void {
        self::assertEquals('Animal', Types::inferExtensionTypeName('Some\\ExtendsAnimalType'));
        // self::assertEquals('Animal', Types::inferExtensionTypeName('Some\\ExtendsAnimalInterface'));
    }

    /**
     * @param string $className
     * @param string $message
     * @return void
     * @throws DefinitionException
     * @dataProvider failingInferExtensionTypeNameDataProvider
     */
    public function testFailingInferExtensionTypeName(string $className, string $message): void {
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage($message);
        Types::inferExtensionTypeName($className);
    }

    public static function failingInferExtensionTypeNameDataProvider(): array
    {
        return [
            'Not starting with extends' => [
                'Some\\OtherAnimalInterface', "Could not infer type name from string: OtherAnimalInterface. Expected string to start with 'Extends'."
            ],
            'Not ending in type nor interface' => [
                'Some\\ExtendsAnimalSomething', "Could not infer type name from string: ExtendsAnimalSomething. Expected string to end in 'Type' or 'Interface'."
            ],
        ];
    }

    public function testIsDirective(): void {
        self::assertTrue(Types::isDirective('Some\\Class\\MyDirective'));
        self::assertTrue(Types::isDirective('MyDirective'));

        self::assertFalse(Types::isDirective('Mydirective'));
        self::assertFalse(Types::isDirective($this->prophesize(DefinesGraphQlType::class)->reveal()));
        self::assertTrue(Types::isDirective($this->prophesize(GraphQlDirective::class)->reveal()));
    }
}
