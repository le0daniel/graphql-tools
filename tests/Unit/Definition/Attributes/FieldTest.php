<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Definition\Attributes;

use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Attributes\Field;
use GraphQlTools\Definition\DefinitionException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;

class FieldTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|TypeRegistry $typeRegistry;

    protected function setUp(): void
    {
        $this->typeRegistry = $this->prophesize(TypeRegistry::class);
    }

    public function testGetTypeWithInference()
    {
        $class = new class () {
            public static function test(): string {
                return '';
            }
        };

        $prophecy = (new ReflectionClass($class))->getMethod('test');
        $this->typeRegistry->string()->shouldBeCalledOnce()->willReturn(Type::string());

        /** @var NonNull $definition */
        $definition = (new Field())->getType(
            $this->typeRegistry->reveal(),
            $prophecy
        );

        self::assertInstanceOf(NonNull::class, $definition);
        self::assertInstanceOf(StringType::class, $definition->getWrappedType());
    }

    public function testGetNullableTypeWithInference()
    {
        $class = new class () {
            public static function test(): ?string {
                return '';
            }
        };

        $prophecy = (new ReflectionClass($class))->getMethod('test');
        $this->typeRegistry->string()->shouldBeCalledOnce()->willReturn(Type::string());

        /** @var StringType $definition */
        $definition = (new Field())->getType(
            $this->typeRegistry->reveal(),
            $prophecy
        );

        self::assertInstanceOf(StringType::class, $definition);
    }

    public function testGetNullableTypeWithUnionInference()
    {
        $class = new class () {
            public static function test(): null|string {
                return '';
            }
        };

        $prophecy = (new ReflectionClass($class))->getMethod('test');
        $this->typeRegistry->string()->shouldBeCalledOnce()->willReturn(Type::string());

        /** @var StringType $definition */
        $definition = (new Field())->getType(
            $this->typeRegistry->reveal(),
            $prophecy
        );

        self::assertInstanceOf(StringType::class, $definition);
    }

    public function testFailureWhenNoPrimitiveScalarIsUsedInference()
    {
        $class = new class () {
            public static function test(): callable {
                return '';
            }
        };

        $prophecy = (new ReflectionClass($class))->getMethod('test');

        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage("Expected valid built in type (string, int, float, boolean, false, true), got: 'callable'");

        (new Field())->getType(
            $this->typeRegistry->reveal(),
            $prophecy
        );
    }

    public function testGetTypeFromString()
    {
        $class = new class () {
            public static function test() {
                return '';
            }
        };

        $prophecy = (new ReflectionClass($class))->getMethod('test');
        $this->typeRegistry->type('MyType')->shouldBeCalledOnce()->willReturn(Type::string());

        /** @var WrappingType $definition */
        $definition = (new Field('[MyType!]!'))->getType(
            $this->typeRegistry->reveal(),
            $prophecy
        );

        self::assertInstanceOf(NonNull::class, $definition);
        self::assertInstanceOf(ListOfType::class, $definition->getWrappedType());
        self::assertInstanceOf(NonNull::class, $definition->getWrappedType()->getWrappedType());
        self::assertInstanceOf(StringType::class, $definition->getInnermostType());
    }
}
