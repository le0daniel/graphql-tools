<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Registry;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Registry\SchemaRegistry;
use GraphQlTools\Helper\Registry\TagBasedSchemaRules;
use GraphQlTools\Test\Dummies\Schema\Input\MamelsQueryInputType;
use GraphQlTools\Test\Dummies\Schema\LionType;
use GraphQlTools\Test\Dummies\Schema\ProtectedUserType;
use GraphQlTools\Test\Dummies\Schema\Stitching\ExtendMamelInterface;
use PHPUnit\Framework\TestCase;

class SchemaRegistrySchemaTest extends TestCase
{

    public function testRegister()
    {
        $federation = new SchemaRegistry();
        $federation->register(LionType::class);
        self::assertTrue(true);
    }

    public function testPartialPrint(): void {
        $schema = new SchemaRegistry();
        $schema->extendType('Mamel', ExtendMamelInterface::class);
        $schema->register(new LionType);
        $schema->register(ProtectedUserType::class);
        $schema->register(MamelsQueryInputType::class);

        self::assertEquals('""
type Lion implements Mamel {
  ""
  sound: String!

  "**Deprecated**: Some reason. Removal Date: 2023-01-09.  Tags: First, Second"
  fieldWithMeta(test: String = "This is a string", else: String = "MEAT"): String! @deprecated(reason: "Some reason")

  ""
  depth: Depth

  ""
  added: String
}

"External type, interface, scalar, union or input type reference not present in the schema"
type Depth

""
type ProtectedUser {
  ""
  secret: String
}

"My description"
input MamelsQueryInput {
  "**Deprecated**: my reason. No removal date specified. "
  name: String!
}

"External type, scalar, union or input type reference not present in the schema"
interface Mamel {
  "@extend(): This field is an extension of an external type"
  added: String
}
', $schema->printPartial());
    }

    public function testRegisterWithInvalidName() {
        $instance = new class () extends GraphQlType {
            public function getName(): string
            {
                return 'my-type';
            }

            protected function fields(TypeRegistry $registry): array
            {
                return [];
            }

            protected function description(): string
            {
                return '';
            }
        };
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage('Could not infer name from class name string.');
        $federation = new SchemaRegistry();
        $federation->register($instance::class);
    }

    public function testWithInvisibleEagerType(): void {
        $type = new class () extends GraphQlType {
            public function getName(): string
            {
                return 'MyType';
            }

            protected function fields(TypeRegistry $registry): array
            {
                return [
                    Field::withName('test')
                        ->ofType($registry->string())
                        ->resolvedBy(fn() => '')
                        ->tags('hidden')
                ];
            }

            protected function description(): string
            {
                return 'something';
            }
        };

        $federation = new SchemaRegistry();
        $federation->register($type);
        $federation->registerEagerlyLoadedType('MyType');

        $config = $federation->createSchemaConfig(
            'MyType',
            schemaRules: new TagBasedSchemaRules(['hidden'])
        );

        // Hide types where all fields are hidden.
        self::assertEmpty(($config->types)());
    }
}
