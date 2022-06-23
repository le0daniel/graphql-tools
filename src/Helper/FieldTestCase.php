<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use GraphQlTools\Utility\Promises;
use RuntimeException;
use Throwable;

class FieldTestCase
{
    private array $dataLoaderMock = [];
    private readonly FieldDefinition $fieldDefinition;

    public function __construct(private readonly string $className, private readonly string $fieldName)
    {
        /** @var GraphQlType $type */
        $type = new ($this->className)($this->mockedTypeRegistry());
        $this->fieldDefinition = $type->findField($this->fieldName);
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

    private function getFieldResolver(): Closure
    {
        return ($this->fieldDefinition->resolveFn)(...);
    }

    private function buildResolveInfo(): ResolveInfo
    {
        return ResolveInfoDummy::withDefaults(path: [
            bin2hex(random_bytes(12)),
            bin2hex(random_bytes(12))
        ], fieldDefinition: $this->fieldDefinition);
    }

    /**
     * @return array<Closure>
     */
    private function buildDataLoaderMocks(): array
    {
        $mocks = [];
        foreach ($this->dataLoaderMock as $key => $returnValue) {
            $mocks[$key] = static fn(array $queuedItems) => $returnValue instanceof Closure ? ($returnValue)($queuedItems) : $returnValue;
        }
        return $mocks;
    }

    private function buildDefaultContext(): Context
    {
        $mocks = $this->buildDataLoaderMocks();
        return new class ($mocks) extends Context {
            public function __construct(private readonly array $mocks)
            {
            }

            protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName): Closure
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

    public function mockedDataloader(string $name, mixed $willReturn): self
    {
        $this->dataLoaderMock[$name] = $willReturn;
        return $this;
    }

    public function visit(mixed $rootData, array $arguments = [], ?Context $context = null, ?ResolveInfo $resolveInfo = null)
    {
        $resolver = $this->getFieldResolver();
        $resolveInfo ??= $this->buildResolveInfo();
        $context ??= $this->buildDefaultContext();
        $operationContext = new OperationContext($context, new ExtensionManager());

        $result = $resolver($rootData, $arguments, $operationContext, $resolveInfo);

        if (!Promises::is($result)) {
            return self::rethrowThrowable($result);
        }

        /** @var SyncPromise $result */
        SyncPromise::runQueue();
        return self::rethrowThrowable($result->result);
    }

}