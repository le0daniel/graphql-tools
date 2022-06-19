<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use ArrayAccess;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\TypeRegistry;
use RuntimeException;
use Throwable;

class FieldTestCase
{
    private array $dataLoaderMock = [];

    public function __construct(private readonly string $className, private readonly string $fieldName)
    {
    }

    private function mockedTypeRegistry(): TypeRegistry
    {
        return new class () extends TypeRegistry {
            public function __construct()
            {
                parent::__construct([]);
            }
        };
    }

    private function getFieldResolver(): callable
    {
        /** @var GraphQlType $type */
        $type = new ($this->className)($this->mockedTypeRegistry());
        $field = $type->findField($this->fieldName);
        return $field->resolveFn;
    }

    private function buildResolveInfo(): ResolveInfo
    {
        return ResolveInfoDummy::withDefaults(path: [
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12))
        ]);
    }

    private function buildDataLoaderMocks(): array
    {
        $mocks = [];
        foreach ($this->dataLoaderMock as $key => $value) {
            $mocks[$key] = new class ($value) implements ExecutableByDataLoader {

                public function __construct(private mixed $returnValue)
                {
                }

                public function fetchData(array $queuedItems): array|ArrayAccess
                {
                    return is_callable($this->returnValue)
                        ? ($this->returnValue)($queuedItems)
                        : $this->returnValue;
                }
            };
        }
        return $mocks;
    }

    private function buildDefaultContext(): Context
    {
        $mocks = $this->buildDataLoaderMocks();
        return new class ($mocks) extends Context {
            public function __construct(private array $mocks)
            {
            }

            protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): ExecutableByDataLoader
            {
                $instance = $this->mocks[$classNameOrLoaderName] ?? null;
                if (!$instance) {
                    throw new RuntimeException("No mock defined for '{$classNameOrLoaderName}'");
                }
                return $instance;
            }
        };
    }

    private static function rethrowThrowable(mixed $throwable): mixed
    {
        if ($throwable instanceof Throwable) {
            throw $throwable;
        }
        return $throwable;
    }

    public function mockedDataloader(string $className, mixed $willReturn): self
    {
        $this->dataLoaderMock[$className] = $willReturn;
        return $this;
    }

    public function visit(mixed $rootData, array $arguments = [], ?Context $context = null, ?ResolveInfo $resolveInfo = null)
    {
        $resolver = $this->getFieldResolver();
        $resolveInfo ??= $this->buildResolveInfo();
        $context ??= $this->buildDefaultContext();
        $operationContext = new OperationContext($context, new Extensions());

        $result = $resolver($rootData, $arguments, $operationContext, $resolveInfo);

        if (!ProxyResolver::isPromise($result)) {
            return self::rethrowThrowable($result);
        }

        /** @var SyncPromise $result */
        SyncPromise::runQueue();
        return self::rethrowThrowable($result->result);
    }

}