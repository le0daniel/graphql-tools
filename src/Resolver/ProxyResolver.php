<?php

declare(strict_types=1);

namespace GraphQlTools\Resolver;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Execution\OperationContext;
use GraphQlTools\Execution\ExtensionManager;

class ProxyResolver
{
    /** @var callable|null */
    private $resolveFunction;

    public function __construct(?callable $resolveFunction = null)
    {
        $this->resolveFunction = $resolveFunction;
    }

    private static function isPromise($potentialPromise): bool
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

        return $typeData->{$fieldName};
    }

    /**
     * This function is a hook before the value is resolved. This can be used to manipulate the arguments and
     * MUST return an array of arguments. This is a good place to validate arguments
     *
     * This is done synchronously as it should only manipulate the arguments.
     *
     * @param array $arguments
     * @param mixed $typeData
     * @param Context $context
     * @param ResolveInfo $info
     * @return array
     */
    protected function manipulateArgumentsBeforeResolution(array $arguments, mixed $typeData, Context $context, ResolveInfo $info): array
    {
        return $arguments;
    }

    /**
     * This can be used to further manipulate the data based on the resolved value. This is called when
     * the value has finally been resolved.
     *
     * @param mixed $resolvedValue
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    protected function manipulateValueAfterResolution(mixed $resolvedValue, Context $context, ResolveInfo $info): mixed
    {
        return $resolvedValue;
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
        $context = $operationContext->context;

        $resolveExtensions = $operationContext->extension->dispatch(
            ExtensionManager::FIELD_RESOLUTION_EVENT, $typeData, $arguments, $info
        );

        try {
            $promiseOrValue = $this->resolveFieldToValue(
                $typeData,
                $this->manipulateArgumentsBeforeResolution($arguments, $typeData, $context, $info),
                $context,
                $info
            );

            // The synchronous case is directly resolved as the value is already preset.
            if (!self::isPromise($promiseOrValue)) {
                $value = $this->manipulateValueAfterResolution($promiseOrValue, $context, $info);
                $resolveExtensions($value);
                return $value;
            }
        } catch (\Throwable $error) {
            $resolveExtensions($error);
            throw $error;
        }

        // In the event of the asynchronous case, resolution and its handlers
        // are called after the resolution was successfully completed.
        return $promiseOrValue
            ->then(function($resolvedValue) use ($context, $info, $resolveExtensions){
                $value = $this->manipulateValueAfterResolution($resolvedValue, $context, $info);
                $resolveExtensions($value);
                return $value;
            })->catch(static function(\Throwable $error) use ($resolveExtensions) {
                $resolveExtensions($error);
                return $error;
            });
    }
}
