<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use Closure;
use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Middleware;

/**
 * @internal
 * Lazily create a middleware resolve function.
 */
final class MiddlewareResolver extends ProxyResolver
{
    public function __construct(private readonly ?Closure $middle, private readonly array $pipes)
    {
        parent::__construct();
    }

    protected function getResolveFunction(): Closure {
        return Middleware::create($this->pipes)->then($this->middle ?? Executor::getDefaultFieldResolver()(...));
    }
}