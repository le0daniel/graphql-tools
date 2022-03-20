<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use ArrayAccess;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Events\VisitFieldEvent;
use Throwable;

final class ProxyResolver
{
    /** @var callable|null */
    private $resolveFunction;

    public function __construct(?callable $resolveFunction = null)
    {
        $this->resolveFunction = $resolveFunction;
    }

    public static function isPromise(mixed $potentialPromise): bool
    {
        return $potentialPromise instanceof SyncPromise || $potentialPromise instanceof Promise;
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
    private function resolveFieldToValue($typeData, array $arguments, Context $context, ResolveInfo $info): mixed
    {
        if ($this->resolveFunction) {
            return ($this->resolveFunction)($typeData, $arguments, $context, $info);
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
     * @param mixed $typeData
     * @param array|null $arguments
     * @param OperationContext $operationContext
     * @param ResolveInfo $info
     * @return mixed
     * @throws Throwable
     */
    public function __invoke(mixed $typeData, ?array $arguments, OperationContext $operationContext, ResolveInfo $info): mixed
    {
        // Ensure arguments are always an array.
        $arguments ??= [];

        $afterFieldResolution = $operationContext->extensions->willVisitField(
            VisitFieldEvent::create($typeData, $arguments, $info)
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
                    ->then(static fn($resolvedValue) => $afterFieldResolution($resolvedValue))
                    ->catch(static fn(Throwable $error) => $afterFieldResolution($error))
                : $afterFieldResolution($promiseOrValue);

        } catch (Throwable $error) {
            return $afterFieldResolution($error);
        }
    }
}
