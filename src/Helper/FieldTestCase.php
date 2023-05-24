<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Helper\Registry\AllVisibleSchemaRule;
use GraphQlTools\Helper\Registry\FactoryTypeRegistry;
use GraphQlTools\Test\Dummies\ResolveInfoDummy;
use Throwable;
use GraphQlTools\Contract\GraphQlContext;

class FieldTestCase
{
    private readonly FieldDefinition $fieldDefinition;
    private array $dataLoaders = [];

    public function __construct(string|DefinesGraphQlType $typeDefinition, string $fieldName)
    {
        /** @var DefinesGraphQlType $type */
        $definition = is_string($typeDefinition) ? new $typeDefinition : $typeDefinition;

        /** @var ObjectType|InputObjectType $type */
        $type = $definition->toDefinition($this->mockedTypeRegistry(), new AllVisibleSchemaRule());
        $this->fieldDefinition = $type->findField($fieldName);
    }

    private function mockedTypeRegistry(): FactoryTypeRegistry
    {
        return new class () extends FactoryTypeRegistry {
            public function __construct()
            {
                parent::__construct([], []);
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

    public function withDataLoader(string $key, Closure $closure): self {
        $this->dataLoaders[$key] = $closure;
        return $this;
    }

    private function buildDefaultContext(): GraphQlContext
    {
        return new class ($this->dataLoaders) implements GraphQlContext {
            use HasDataloaders;

            public function __construct(private readonly array $dataLoaders = [])
            {
            }

            public function makeInstanceOfDataLoaderExecutor(string $key): Closure {
                return $this->dataLoaders[$key];
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

    public function visit(mixed $rootData, array $arguments = [], ?GraphQlContext $context = null, ?ResolveInfo $resolveInfo = null)
    {
        $resolver = $this->getFieldResolver();
        $resolveInfo ??= $this->buildResolveInfo();
        $context ??= $this->buildDefaultContext();
        $operationContext = new OperationContext($context, new ExtensionManager());

        $result = $resolver($rootData, $arguments, $operationContext, $resolveInfo);

        if (!$result instanceof SyncPromise) {
            return self::rethrowThrowable($result);
        }

        /** @var SyncPromise $result */
        SyncPromise::runQueue();
        return self::rethrowThrowable($result->result);
    }

}