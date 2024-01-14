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
use Prophecy\PhpUnit\ProphecyTrait;

class SchemaRegistrySchemaTest extends TestCase
{
    use ProphecyTrait;

    public function testRegister()
    {
        $federation = new SchemaRegistry();
        $federation->register(LionType::class);
        self::assertTrue(true);
    }

    public function testRegisterWithInvalidName()
    {
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

    public function testFailureWhenRegisteringTheSameNameTwice(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Type with name \'My\' was already registered. You can not register a type twice.');

        $federation = new SchemaRegistry();
        $federation->register('MyType');
        $federation->register('MyType');
    }

    public function testFailureWhenTypeNameMismatchesBetweenHintAndDefinition(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Definition name did not match provided name");

        $type = $this->prophesize(DefinesGraphQlType::class);
        $type->getName()->willReturn('Else');

        $federation = new SchemaRegistry();
        $federation->register($type->reveal(), 'Something');
    }

    public function testWithInvisibleEagerType(): void
    {
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
