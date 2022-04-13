<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

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

    public function __construct(private string $className, private string $fieldName)
    {
    }

    private function mockedTypeRegistry(): TypeRegistry {
        return new class () extends TypeRegistry {
            public function __construct()
            {
                parent::__construct([]);
            }
        };
    }

    private function getFieldResolver(): callable {
        /** @var GraphQlType $type */
        $type = new ($this->className)($this->mockedTypeRegistry());
        $field = $type->findField($this->fieldName);
        return $field->resolveFn;
    }

    private function defaultResolveInfo(): ResolveInfo {
        return ResolveInfoDummy::withDefaults(path: [
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12))
        ]);
    }

    public function mockedDataloader(string $className, mixed $willReturn): self {
        $this->dataLoaderMock[$className] = $willReturn;
        return $this;
    }

    private function buildDataLoaderMocks(): array {
        $mocks = [];
        foreach ($this->dataLoaderMock as $key => $value) {
            $mocks[$key] = new class ($value) implements ExecutableByDataLoader {

                public function __construct(private mixed $value)
                {
                }

                public function fetchData(array $queuedItems, array $arguments): mixed
                {
                    return is_callable($this->value) ? ($this->value)($queuedItems, $arguments) : $this->value;
                }
            };
        }
        return $mocks;
    }

    private function buildDefaultContext(): Context {
        $mocks = $this->buildDataLoaderMocks();
        return new class ($mocks) extends Context {
            public function __construct(private array $mocks)
            {
            }

            protected function makeInstanceOfDataLoaderExecutor(string $className): ExecutableByDataLoader
            {
                $instance = $this->mocks[$className] ?? null;
                if (!$instance) {
                    throw new RuntimeException("No mock defined for '{$className}'");
                }
                return $instance;
            }
        };
    }

    private static function rethrowThrowable(mixed $throwable): mixed {
        if ($throwable instanceof Throwable) {
            throw $throwable;
        }
        return $throwable;
    }

    public function visit(mixed $rootData, array $arguments = [], ?Context $context = null, ?ResolveInfo $resolveInfo = null)
    {
        $resolver = $this->getFieldResolver();
        $resolveInfo ??= $this->defaultResolveInfo();
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