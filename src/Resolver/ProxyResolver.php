<?php

declare(strict_types=1);

namespace GraphQlTools\Resolver;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Execution\OperationContext;
use GraphQlTools\Utility\SideEffects;

class ProxyResolver
{
    /** @var callable|null */
    private $resolveFunction;

    public function __construct(?callable $resolveFunction = null)
    {
        $this->resolveFunction = $resolveFunction;
    }

    private static function isPromise(mixed $potentialPromise): bool
    {
        return $potentialPromise instanceof SyncPromise || $potentialPromise instanceof Promise;
    }

    /**
     * @throws \Throwable
     */
    final public static function default(): mixed
    {
        return (new static())->__invoke(...func_get_args());
    }

    /**
     * Overwrite this method to define your own resolver.
     *
     * @param $typeData
     * @param array $arguments
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    protected function resolveFieldToValue($typeData, array $arguments, Context $context, ResolveInfo $info): mixed
    {
        if ($this->resolveFunction) {
            return call_user_func($this->resolveFunction, $typeData, $arguments, $context, $info);
        }

        $fieldName = $info->fieldName;
        if (is_array($typeData) || $typeData instanceof \ArrayAccess) {
            return $typeData[$fieldName] ?? null;
        }

        if (!is_object($typeData)) {
            return null;
        }

        /** @var $typeData object */
        return $typeData->{$fieldName} ?? null;
    }

    /**
     * This method is invoked when a field gets resolved. It is responsible to delegate the resolution and
     * call the necessary extensions.
     *
     * @param mixed $typeData
     * @param array|null $arguments
     * @param OperationContext $operationContext
     * @param ResolveInfo $info
     * @return mixed
     * @throws \Throwable
     */
    final public function __invoke(mixed $typeData, ?array $arguments, OperationContext $operationContext, ResolveInfo $info): mixed
    {
        $arguments ??= [];
        $next = $operationContext->extensions->middlewareFieldResolution(
            FieldResolutionEvent::create($typeData, $arguments, $info)
        );

        try {
            $promiseOrValue = $this->resolveFieldToValue(
                $typeData,
                $arguments,
                $operationContext->context,
                $info
            );

            return self::isPromise($promiseOrValue)
                ? $promiseOrValue
                    ->then(static fn($resolvedValue) => $next($resolvedValue))
                    ->catch(static fn(\Throwable $error) => $next($error))
                : $next($promiseOrValue);

        } catch (\Throwable $error) {
            return $next($error);
        }
    }
}
