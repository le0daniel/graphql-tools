<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Definition;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\EnumValue;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Helper\Registry\AllVisibleSchemaRule;
use GraphQlTools\Test\Dummies\Enum\Eating;
use GraphQlTools\Test\Dummies\Enum\NotBackedEnum;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class GraphQlEnumTest extends TestCase
{
    use ProphecyTrait;

    private function instance(array|string $values, string $description): GraphQlEnum {
        return new class ($values, $description) extends GraphQlEnum {

            public function __construct(private readonly array|string $values, private readonly string $description)
            {
            }

            protected function values(): array|string
            {
                return $this->values;
            }

            protected function description(): string
            {
                return $this->description;
            }

            public function getName(): string
            {
                return 'Name';
            }
        };
    }

    /**
     * @dataProvider toDefinitionDataProvider
     */
    public function testToDefinition(array|string $values , ?string $description = null)
    {
        $description ??= 'some description';
        $instance = $this->instance($values, $description);
        $definition = $instance->toDefinition($this->prophesize(TypeRegistry::class)->reveal(), new AllVisibleSchemaRule());
        $definition->assertValid();

        self::assertEquals($description, $definition->description);
    }

    public function toDefinitionDataProvider(): array {
        return [
            'with array values' => [['one', 'two', 'three']],
            'with key values pairs' => [['one' => ['value' => true]]],
            'with deprecated' => [['one' => ['value' => true, 'deprecatedReason' => 'something']]],
            'with enum as value' => [Eating::class],
            'with enum values' => [
                [Eating::MEAT, Eating::WOOD, Eating::VEGAN]
            ],
            'with unbacked enum' => [NotBackedEnum::class],
            'with unbacked enum values' => [NotBackedEnum::cases()],
            'with defined enum values' => [
                [
                    EnumValue::fromEnum(Eating::MEAT),
                    EnumValue::fromEnum(NotBackedEnum::ONE),
                    EnumValue::withName('TEST')->deprecated('because it is'),
                    EnumValue::withName('TEST2')->value('else')
                ]
            ]
        ];
    }

    public function testTypeName()
    {
        self::assertEquals('Name', $this->instance([], '')->getName());
    }
}
