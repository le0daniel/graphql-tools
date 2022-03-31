<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Helper\ContextualDataLoader;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Utility\Paths;

final class Field extends GraphQlField
{
    /** @var ContextualDataLoader[] */
    private array $deferredLoaders = [];

    /** @var callable|null */
    protected $mappingFunction = null;

    /** @var callable|null */
    protected $resolveDataFunction = null;

    /** fn(array $queuedData, array $arguments, Context $context, ...Injections) => notNull */
    public function resolveData(Closure $callable): self
    {
        $this->resolveDataFunction = function (array $queuedData, array $arguments, Context $context) use ($callable) {
            return $context->executeResolveDataFunction(
                $callable, $queuedData, $this->validateArguments($arguments)
            );
        };
        return $this;
    }

    /** fn(mixed $data, array $arguments, array $loadedData) => notNull */
    public function mappedBy(Closure $closure): self
    {
        $this->mappingFunction = $closure;
        return $this;
    }

    /**
     * Creates a unique deferred loader by arguments and path. This ensures no collisions exist
     *
     * @param array $arguments
     * @param ResolveInfo $resolveInfo
     * @return ContextualDataLoader
     * @throws \JsonException
     */
    private function getContextualDeferredLoader(array $arguments, ResolveInfo $resolveInfo): ContextualDataLoader
    {
        $path = Paths::toString($resolveInfo->path);
        $serializedArguments = json_encode($arguments, JSON_THROW_ON_ERROR);
        $key = "{$path}::{$serializedArguments}";

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualDataLoader(
                $this->resolveDataFunction,
                $this->mappingFunction,
                $arguments
            );
        }

        return $this->deferredLoaders[$key];
    }

    private function verifyMappingFunctionIsSet(): void {
        if (!$this->mappingFunction) {
            throw DefinitionException::fromMissingFieldDeclaration(
                'mappedBy', $this->name, "Use ->mappedBy to define a mapping function for fields using ->resolveData"
            );
        }
    }

    protected function getResolver(): ProxyResolver
    {
        $isSimpleField = !$this->resolveDataFunction;

        if ($isSimpleField) {
            return new ProxyResolver($this->mappingFunction
                ? fn($data, $arguments) => ($this->mappingFunction)($data, $this->validateArguments($arguments))
                : null,
            );
        }

        $this->verifyMappingFunctionIsSet();
        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
            return $this
                ->getContextualDeferredLoader($arguments, $resolveInfo)
                ->defer($data, $context);
        });
    }
}