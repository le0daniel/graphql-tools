<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use ArrayAccess;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Utility\Promises;
use Throwable;

final class ProxyResolver
{
    public function __construct(private readonly ?Closure $resolveFunction = null)
    {
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
    private function resolveToValue($typeData, array $arguments, Context $context, ResolveInfo $info): mixed
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

        $afterFieldResolution = $operationContext->extensionManager->willResolveField(
            VisitFieldEvent::create($typeData, $arguments, $info)
        );

        try {
            $promiseOrValue = $this->resolveToValue(
                $typeData,
                $arguments,
                $operationContext->context,
                $info
            );

            return Promises::is($promiseOrValue)
                ? $promiseOrValue
                    ->then(static fn(mixed $resolvedValue): mixed => $afterFieldResolution($resolvedValue))
                    ->catch(static fn(Throwable $error): Throwable => $afterFieldResolution($error))
                : $afterFieldResolution($promiseOrValue);

        } catch (Throwable $error) {
            return $afterFieldResolution($error);
        }
    }
}
