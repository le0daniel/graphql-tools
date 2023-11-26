<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
use GraphQlTools\Helper\Context;
use GraphQlTools\Helper\Extensions;
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
    private readonly Closure $resolveFunction;

    public function __construct(
        private readonly ?Closure $fieldResolveFunction = null,
        private readonly array    $middlewares = [],
    )
    {
    }

    private function createResolveFunction(): Closure
    {
        return Middleware::create($this->middlewares)
            ->then($this->fieldResolveFunction ?? Executor::getDefaultFieldResolver()(...));
    }

    private function attachDirectiveMiddlewares(Closure $resolver, ResolveInfo $info): Closure
    {
        if (
            self::$disableDirectives
            || empty($directiveNames = Directives::getNamesByResolveInfo($info))
            || empty($pipes = Directives::createPipes($info, $directiveNames))
        ) {
            return $resolver;
        }

        return Middleware::create($pipes)->then($resolver);
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
        /**
         * If the result has been cached previously, we get it from cache, skipping everything. This enables extensions to
         * collect data only once and not be run multiple times.
         */
        if ($operationContext->isInResult($info->path)) {
            return $operationContext->getFromResult($info->path);
        }

        // Ensure arguments are always an array, as the framework does not guarantee that
        $arguments ??= [];

        // We first verify if in a previous run this has been deferred
        // If this is the case, we mark it as hasBeenDeferred and take the type data
        // from the last run to ensure the resolver works as intended.
        $hasBeenDeferred = $operationContext->isDeferred($info->path);
        if ($hasBeenDeferred) {
            $typeData = $operationContext->popDeferred($info->path);
        }

        /** @var VisitFieldEvent $fieldResolution */
        $fieldResolution = VisitFieldEvent::create($typeData, $arguments, $info, $hasBeenDeferred);
        $operationContext->willResolveField($fieldResolution);

        // As the field has been deferred, we return null. If multiple runs are enabled, this will
        // result in the field being run next time. This can only happen once.
        if ($fieldResolution->shouldDefer() && !$hasBeenDeferred) {
            $operationContext->deferField($info->path, $fieldResolution->getDeferLabel(), $typeData);
            return null;
        }

        // Hook after the field and all it's promises have been executed.
        // This is where extensions can hook in. They are though not allowed to manipulate the result.
        $afterFieldResolution = static function (mixed $value) use ($fieldResolution, $operationContext): mixed {
            return $fieldResolution->resolveValue($value);
        };

        try {
            $resolveFn = $this->attachDirectiveMiddlewares(
                $this->resolveFunction ??= $this->createResolveFunction(),
                $info
            );

            /** @var SyncPromise|mixed $promiseOrValue */
            $promiseOrValue = $resolveFn(
                $typeData,
                $arguments,
                $operationContext->getContext(),
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
