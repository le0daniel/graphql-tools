<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use ArrayAccess;
use Closure;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Promises;
use Throwable;

class ProxyResolver
{
    /**
     * Allows to overwrite the default resolver behaviour.
     *
     * @var Closure(mixed $typeData, array $arguments, GraphQlContext $context, ResolveInfo $info): mixed|null
     */
    public static ?Closure $defaultResolver = null;

    public function __construct(private readonly ?Closure $resolveFunction = null)
    {
    }

    /**
     * Overwrite this method to define your own resolver.
     *
     * @template T
     * @param T $typeData
     * @param array<string, mixed> $arguments
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     * @internal
     */
    public function resolveToValue(mixed $typeData, array $arguments, GraphQlContext $context, ResolveInfo $info): mixed
    {
        return $this->resolveFunction
            ? ($this->resolveFunction)($typeData, $arguments, $context, $info)
            : self::resolveDefault($typeData, $arguments, $context, $info);
    }

    protected static function resolveDefault(mixed $typeData, array $arguments, GraphQlContext $context, ResolveInfo $info): mixed {
        if (self::$defaultResolver) {
            return (self::$defaultResolver)($typeData, $arguments, $context, $info);
        }

        $fieldName = $info->fieldName;
        if (is_array($typeData) || $typeData instanceof ArrayAccess) {
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
     * @template T
     * @param T $typeData
     * @param array<string, mixed>|null $arguments
     * @param OperationContext $operationContext
     * @param ResolveInfo $info
     * @return mixed
     * @throws Throwable
     */
    final public function __invoke(mixed $typeData, ?array $arguments, OperationContext $operationContext, ResolveInfo $info): mixed
    {
        // Ensure arguments are always an array, as the framework does not guarantee that
        $arguments ??= [];

        $afterFieldResolution = $operationContext->extensionManager->willResolveField(
            VisitFieldEvent::create($typeData, $arguments, $info)
        );

        try {
            /** @var SyncPromise|mixed $promiseOrValue */
            $promiseOrValue = $this->resolveToValue(
                $typeData,
                $arguments,
                $operationContext->context,
                $info
            );

            return Promises::isPromise($promiseOrValue)
                ? $promiseOrValue
                    ->then(static fn(mixed $resolvedValue): mixed => $afterFieldResolution($resolvedValue))
                    ->catch(static fn(Throwable $error): Throwable => $afterFieldResolution($error))
                : $afterFieldResolution($promiseOrValue);

        } catch (Throwable $error) {
            return $afterFieldResolution($error);
        }
    }
}
