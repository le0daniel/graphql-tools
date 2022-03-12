<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Helper\ContextualDataLoader;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Utility\Paths;

class DeferredField extends GraphQlField
{

    /** @var ContextualDataLoader[] */
    private array $deferredLoaders = [];

    /** @var callable */
    private $resolveItem;

    /** @var callable */
    private $resolveAggregated;

    private function getContextualDeferredLoader(array $arguments, Context $context, ResolveInfo $resolveInfo): ContextualDataLoader {
        $key = Paths::toString($resolveInfo->path) . ':' . json_encode($arguments, JSON_THROW_ON_ERROR);

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualDataLoader($this->resolveAggregated, $this->resolveItem, $arguments, $context);
        }

        return $this->deferredLoaders[$key];
    }

    /**
     * Callable fn(array $queuedData, array $validatedArguments, Context $context) => mixed
     *
     * @param callable $callable
     * @return $this
     */
    public function resolveAggregated(callable $callable): static {
        $this->resolveAggregated = function (array $queuedData, array $arguments, Context $context) use ($callable) {
            return $context->executeAggregatedLoadingFunction(
                $callable, $queuedData, $this->validateArguments($arguments)
            );
        };
        return $this;
    }

    /**
     * This function is responsible for returning the correct data for the current element.
     * Callable fn(mixed $typeData, array $loadedData, Context $context) => mixed
     *
     * @param Closure $callable
     * @return $this
     */
    public function resolveItem(Closure $callable): static {
        $this->resolveItem = $callable;
        return $this;
    }

    protected function getResolver(): ProxyResolver
    {
        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
            return $this->getContextualDeferredLoader($arguments, $context, $resolveInfo)
                ->defer($data);
        });
    }
}