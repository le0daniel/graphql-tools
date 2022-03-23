<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
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
    protected $resolveFunction = null;

    /** fn(array $queuedData, array $arguments, Context $context, ...Injections) => notNull */
    public function resolveData(Closure $callable): self {
        $this->resolveFunction = function (array $queuedData, array $arguments, Context $context) use ($callable) {
            return $context->executeResolveFunction(
                $callable, $queuedData, $this->validateArguments($arguments)
            );
        };
        return $this;
    }

    /** fn(mixed $data, array $arguments, array $loadedData) => notNull */
    public function mappedBy(Closure $closure): self {
        $this->mappingFunction = $closure;
        return $this;
    }

    private function getContextualDeferredLoader(array $arguments, Context $context, ResolveInfo $resolveInfo): ContextualDataLoader {
        $path = Paths::toString($resolveInfo->path);
        $serializedArguments = json_encode($arguments, JSON_THROW_ON_ERROR);
        $key = "{$path}::{$serializedArguments}";

        if (!isset($this->deferredLoaders[$key])) {
            $this->deferredLoaders[$key] = new ContextualDataLoader($this->resolveFunction, $this->mappingFunction, $arguments, $context);
        }

        return $this->deferredLoaders[$key];
    }


    protected function getResolver(): ProxyResolver {
        if (!$this->resolveFunction) {
            // Theoretical access to more than just data and arguments
            return new ProxyResolver($this->mappingFunction
                ? fn($data, $arguments) => ($this->mappingFunction)($data, $this->validateArguments($arguments))
                : null,
            );
        }

        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) {
            return $this->getContextualDeferredLoader($arguments, $context, $resolveInfo)
                ->defer($data);
        });
    }
}