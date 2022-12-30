<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Definition;

use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Test\Dummies\Enum\Eating;
use PHPUnit\Framework\TestCase;

class GraphQlEnumTest extends TestCase
{

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

            public static function typeName(): string
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
        $definition = $instance->toDefinition();
        $definition->assertValid();

        self::assertEquals($description, $definition->description);
    }

    public function toDefinitionDataProvider(): array {
        return [
            'with array values' => [['one', 'two', 'three']],
            'with key values pairs' => [['one' => ['value' => true]]],
            'with deprecated' => [['one' => ['value' => true, 'deprecatedReason' => 'something']]],
            'with enum values' => [Eating::class],
        ];
    }

    public function testTypeName()
    {
        self::assertEquals('Name', $this->instance([], '')::typeName());
    }
}
