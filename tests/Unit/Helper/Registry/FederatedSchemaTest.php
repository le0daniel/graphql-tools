<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Registry;

use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Registry\FederatedSchema;
use GraphQlTools\Helper\Registry\TagBasedSchemaRules;
use GraphQlTools\Test\Dummies\Schema\LionType;
use PHPUnit\Framework\TestCase;

class FederatedSchemaTest extends TestCase
{

    public function testRegister()
    {
        $federation = new FederatedSchema();
        $federation->register(LionType::class);
        $federation->verifyTypeNames();
        self::assertTrue(true);
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
        $federation = new FederatedSchema();
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
                        ->ofType(Type::string())
                        ->resolvedBy(fn() => '')
                        ->tags('hidden')
                ];
            }

            protected function description(): string
            {
                return 'something';
            }
        };

        $federation = new FederatedSchema();
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
