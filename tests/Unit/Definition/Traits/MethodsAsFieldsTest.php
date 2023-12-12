<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Definition\Traits;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Attributes\Field;
use GraphQlTools\Definition\Traits\MethodsAsFields;
use GraphQlTools\Helper\Registry\AllVisibleSchemaRule;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MethodsAsFieldsTest extends TestCase
{
    use ProphecyTrait;

    public function testFields(): void {
        $instance = new class () {
            use MethodsAsFields;

            public function getFields(TypeRegistry $registry): array {
                return $this->fields($registry);
            }

            #[Field]
            public static function test(): ?string {
                return 'null';
            }
        };
        $typeRegistry = $this->prophesize(TypeRegistry::class);
        $typeRegistry->string()->shouldBeCalledOnce()->willReturn(Type::string());

        $fields = $instance->getFields($typeRegistry->reveal());
        self::assertCount(1, $fields);

        /** @var \GraphQlTools\Definition\Field\Field $field */
        $field = $fields[0];
        self::assertEquals('test', $field->name);

        $definition = $field->toDefinition(new AllVisibleSchemaRule());
        self::assertEquals('String', $definition->getType()->toString());
    }

}
