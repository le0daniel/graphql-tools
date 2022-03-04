<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\TypeRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use RuntimeException;
use Throwable;

abstract class TypeTestCase extends TestCase
{
    use ProphecyTrait;

    abstract protected function typeClassName(): string;

    protected function getFieldByName(string $fieldName): GraphQlField
    {
        $typeReflection = new ReflectionClass($this->typeClassName());
        $type = $typeReflection->newInstanceWithoutConstructor();

        $fieldsMethod = $typeReflection->getMethod('fields');
        $fieldsMethod->setAccessible(true);

        /** @var GraphQlField $field */
        foreach ($fieldsMethod->invoke($type) as $field) {
            if ($field->name === $fieldName) {
                return $field;
            }
        }

        throw new RuntimeException("Could not find field with name '{$fieldName}' on type '{$this->typeClassName()}'");
    }

    protected function expectVisitException(
        string   $exceptionMessage,
        string   $fieldName,
        mixed    $rootData,
        ?array   $arguments = null,
        ?Context $context = null
    ): Throwable
    {
        $result = $this->visitField($fieldName, $rootData, $arguments, $context);
        if (!$result instanceof Throwable) {
            $this->fail("Expected to fail but succeeded with message '{$exceptionMessage}' when visiting field '{$fieldName}' on type {$this->typeClassName()}");
        }

        $this->assertEquals($exceptionMessage, $result->getMessage());
        return $result;
    }

    protected function visitField(string $fieldName, mixed $rootData, ?array $arguments = null, ?Context $context = null): mixed
    {
        $field = $this->getFieldByName($fieldName);
        $fieldReflection = new \ReflectionObject($field);

        $resolverMethod = $fieldReflection->getMethod('getResolver');
        $resolverMethod->setAccessible(true);
        $resolver = $resolverMethod->invoke($field);

        $resolveInfo = ResolveInfoDummy::withDefaults(path: [
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12))
        ]);

        $result = $resolver($rootData, $arguments ?? [], new OperationContext(
            $context ?? new Context(), new Extensions()
        ), $resolveInfo);

        if (ProxyResolver::isPromise($result)) {
            /** @var SyncPromise $result */
            SyncPromise::runQueue();
            return $result->result;
        }

        return $result;
    }

}