<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\Registry;

use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\Registry\FederatedSchema;
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
}
