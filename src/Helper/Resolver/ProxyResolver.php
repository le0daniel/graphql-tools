<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
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

    private function attachDirectiveMiddlewares(Closure $resolver, ResolveInfo $info, array $directiveNames): Closure
    {
        if (
            self::$disableDirectives
            || empty($directiveNames)
            || empty($pipes = Directives::createMiddlewares($info, $directiveNames))
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

        /**
         * In case the resolver has been deferred, we need to get the original $typeData from the resolver
         * as the previous resolvers have been resolved from the cache, meaning the $typeData is not what
         * is expected
         */
        $typeData = $hasBeenDeferred ? $context->executor->popDeferred($info->path) : $typeData;

        $fieldResolution = new VisitFieldEvent(
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

        // Hook after the field and all it's promises have been executed.
        // This is where extensions can hook in. They are though not allowed to manipulate the result.
        $afterFieldResolution = static fn(mixed $value): mixed => $fieldResolution->resolveValue($value);

        try {
            $resolveFn = $this->attachDirectiveMiddlewares(
                $this->resolveFunction ??= $this->createResolveFunction(),
                $info,
                $fieldResolution->directiveNames,
            );

            /** @var SyncPromise|mixed $promiseOrValue */
            $promiseOrValue = $resolveFn(
                $typeData,
                $arguments,
                $context->context,
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
