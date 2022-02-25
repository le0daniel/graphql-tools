<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Deferred;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\DataLoader\ContextualLoader;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\Utility\Paths;

class DeferredField extends SimpleField
{
    /** @var ContextualLoader[] */
    private array $deferredLoaders = [];

    /** @var callable */
    private $resolveItem;

    /** @var callable */
    private $resolveAggregated;

    public function withResolver(callable $resolveFunction): static
    {
        throw new \RuntimeException("With resolve is not available for deferred fields. Use `resolveAggregated` and `resolveItems` instead.");
    }

    private function getDeferredLoader(array $arguments, Context $context, ResolveInfo $resolveInfo): ContextualLoader {
        $key = Paths::toString($resolveInfo->path) . ':' . json_encode($arguments);

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualLoader($this->resolveAggregated, $arguments, $context);
        }

        return $this->deferredLoaders[$key];
    }

    protected function getResolver(): ProxyResolver
    {
        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
            return $this->getDeferredLoader($arguments, $context, $resolveInfo)->defer($data, $this->resolveItem);
        });
    }

    public function resolveAggregated(callable $callable): static {
        $this->resolveAggregated = $callable;
        return $this;
    }

    public function resolveItem(callable $callable): static {
        $this->resolveItem = $callable;
        return $this;
    }

}