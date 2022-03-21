<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionClass;
use ReflectionObject;
use RuntimeException;
use Throwable;

abstract class TypeTestCase extends TestCase
{
    abstract protected function typeClassName(): string;

    private function getFieldByName(string $fieldName): GraphQlField
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

    /**
     * Mock injections into a Field with specific implementations for
     *
     * @param array $mockedInstances
     * @return Context
     */
    protected function contextWithMocks(array $mockedInstances = []): Context {
        return new class ($mockedInstances) extends Context {
            public function __construct(private array $mockedClasses){}

            protected function injectInstance(string $className): mixed
            {
                if (array_key_exists($className, $this->mockedClasses)) {
                    return $this->mockedClasses[$className];
                }

                foreach ($this->mockedClasses as $mockedInstance) {
                    if ($mockedInstance instanceof $className) {
                        return $mockedInstance;
                    }
                }

                $reflection = new ReflectionClass($className);
                if ($reflection->isAbstract()) {
                    throw new RuntimeException("Expected class to mock, got interface or abstract class of type: '{$reflection->getName()}'");
                }

                return $reflection->newInstanceWithoutConstructor();
            }
        };
    }

    protected function visitField(string $fieldName, mixed $rootData, ?array $arguments = null, ?Context $context = null, ?ResolveInfo $resolveInfo = null): mixed
    {
        $field = $this->getFieldByName($fieldName);
        $fieldReflection = new ReflectionObject($field);

        $resolverMethod = $fieldReflection->getMethod('getResolver');
        $resolverMethod->setAccessible(true);
        $resolver = $resolverMethod->invoke($field);

        $resolveInfo ??= ResolveInfoDummy::withDefaults(path: [
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12))
        ]);

        $result = $resolver($rootData, $arguments ?? [], new OperationContext(
            $context ?? $this->contextWithMocks(), new Extensions()
        ), $resolveInfo);

        if (ProxyResolver::isPromise($result)) {
            /** @var SyncPromise $result */
            SyncPromise::runQueue();
            return $result->result;
        }

        return $result;
    }

}