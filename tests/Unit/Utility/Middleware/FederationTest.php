<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Utility\Middleware;

use ArrayAccess;
use Closure;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\Resolver\ProxyResolver;
use GraphQlTools\Utility\Middleware\Federation;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

class FederationTest extends TestCase
{
    use ProphecyTrait;

    private function executeMiddlewareWith(Closure $middleware, $carry): mixed {
        return $middleware($carry, null, null, null, fn($value) => $value);
    }

    public function testWithArray()
    {
        self::assertNull($this->executeMiddlewareWith(Federation::key('id'), []));
        self::assertEquals('my-id', $this->executeMiddlewareWith(Federation::key('id'), ['id' => 'my-id']));
    }

    public function testWithObject() {
        $object = new stdClass();
        $object->name = 'string';
        self::assertEquals('string', $this->executeMiddlewareWith(Federation::key('name'), $object));
    }

    public function testWithDynamicObjectProperty(): void {
        $object = new class {
            public function __isset(string $name): bool
            {
                return $name === 'id';
            }
            public function __get(string $name)
            {
                return $name === 'id' ? 12 : null;
            }
        };

        self::assertEquals(12, $this->executeMiddlewareWith(Federation::key('id'), $object));
    }

    public function testWithGetterMethod() {
        $instance = new class () {
            public function getName(): string {
                return 'my-name';
            }
        };
        self::assertEquals('my-name', $this->executeMiddlewareWith(Federation::key('getName'), $instance));
    }

    public function testWithArrayAccess() {
        $instance = new class () implements ArrayAccess {
            public function offsetExists(mixed $offset): bool
            {
                return $offset === 'name';
            }

            public function offsetGet(mixed $offset): mixed
            {
                return 'my-value';
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {

            }

            public function offsetUnset(mixed $offset): void
            {

            }
        };
        self::assertEquals('my-value', $this->executeMiddlewareWith(Federation::key('name'), $instance));
    }

    public function testWithArrayAccessWithNonExistingValue() {
        $instance = new class () implements ArrayAccess {
            public function offsetExists(mixed $offset): bool
            {
                return $offset === 'name';
            }

            public function offsetGet(mixed $offset): mixed
            {
                return null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {

            }

            public function offsetUnset(mixed $offset): void
            {

            }
        };
        self::assertNull($this->executeMiddlewareWith(Federation::key('something'), $instance));
    }

    public function testWithObjectAndMissingProperty() {
        $instance = new class () {
            public function getName(): string {
                return 'my-name';
            }
        };

        self::assertNull( $this->executeMiddlewareWith(Federation::key('name'), $instance));
    }

    public function testFederationField() {
        $resolveInfo = $this->prophesize(ResolveInfo::class)->reveal();
        $parentType = $this->prophesize(ObjectType::class);
        $field = $this->prophesize(FieldDefinition::class);
        $field->getType()->willReturn($this->prophesize(Type::class)->reveal());
        $field->name = 'random';
        $field->resolveFn = new ProxyResolver(fn() => 'value');
        $parentType->getField('fieldName')->willReturn($field->reveal());
        $resolveInfo->parentType = $parentType->reveal();
        $resolveInfo->schema = $this->prophesize(Schema::class)->reveal();
        $resolveInfo->operation = $this->prophesize(OperationDefinitionNode::class)->reveal();

        $pipe = Federation::field('fieldName');
        $result = $pipe(null, [], new Context(), $resolveInfo, fn($val) => $val);
        self::assertEquals('value', $result);
    }
}
