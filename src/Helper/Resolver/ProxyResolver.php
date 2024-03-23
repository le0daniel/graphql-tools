<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Data\ValueObjects\Events\FieldResolution;
use GraphQlTools\Helper\Middleware;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Directives;
use GraphQlTools\Utility\Promises;
use Throwable;

/**
 * This class decorates all resolve functions to provide extension functionality.
 * @internal
 */
class ProxyResolver
{
    public static bool $disableDirectives = false;

    public function __construct(
        private readonly ?Closure $fieldResolveFunction = null,
        private readonly array    $middlewares = [],
    )
    {
    }

    private function directiveMiddlewares(ResolveInfo $info, array $directiveNames): array
    {
        if (self::$disableDirectives || empty($directiveNames)) {
            return [];
        }

        return Directives::getMiddlewares($info, $directiveNames);
    }

    private function wrapResult(mixed $result, FieldResolution $fieldResolution): mixed {
        return Promises::isPromise($result)
            ? $result
                ->then($fieldResolution->resolveValue(...))
                ->catch($fieldResolution->resolveValue(...))
            : $fieldResolution->resolveValue($result);
    }

    /**
     * This method is invoked when a field gets resolved. It is responsible to delegate the resolution and
     * call the necessary extensions.
     *
     * @template T
     * @param T $typeData
     * @param array<string, mixed>|null $arguments
     * @param OperationContext $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws Throwable
     */
    final public function __invoke(mixed $typeData, ?array $arguments, OperationContext $context, ResolveInfo $info): mixed
    {
        /**
         * If the result has been cached previously, we get it from cache, skipping everything. This enables extensions to
         * collect data only once and not be run multiple times. This will skip all additional logic.
         */
        $hasBeenDeferred = $context->executor->isDeferred($info->path);
        if (!$hasBeenDeferred && $context->cache->isInResult($info->path)) {
            return $context->cache->getFromResult($info->path);
        }

        $arguments ??= [];
        $typeData = $hasBeenDeferred ? $context->executor->popDeferred($info->path) : $typeData;

        $fieldResolution = new FieldResolution(
            $typeData,
            $arguments,
            $info,
            !$hasBeenDeferred && $context->executor->canExecuteAgain(),
            Directives::getNamesByResolveInfo($info),
        );

        $context->extensions->willResolveField($fieldResolution);

        // As the field has been deferred, we return null. If multiple runs are enabled, this will
        // result in the field being run next time. This can only happen once and if the executor
        // allows it to happen.
        if ($fieldResolution->isDeferred()) {
            $context->executor->addDefer($info->path, $fieldResolution->getDeferLabel(), $typeData);
            return null;
        }

        try {
            $resolveFn = Middleware::create([
                ... $this->directiveMiddlewares($info, $fieldResolution->directiveNames),
                ... $this->middlewares,
            ])->then($this->fieldResolveFunction ?? Executor::getDefaultFieldResolver()(...));

            return $this->wrapResult(
                $resolveFn($typeData, $arguments, $context->context, $info),
                $fieldResolution
            );
        } catch (Throwable $throwable) {
            return $fieldResolution->resolveValue($throwable);
        }
    }
}
