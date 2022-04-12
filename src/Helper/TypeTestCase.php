<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionObject;
use RuntimeException;
use Throwable;

abstract class TypeTestCase extends TestCase
{
    abstract protected function typeClassName(): string;

    final protected function field(string $name): FieldTestCase {
        return new FieldTestCase($this->typeClassName(), $name);
    }

}