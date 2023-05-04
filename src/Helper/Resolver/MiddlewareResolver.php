<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Resolver;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Middleware;

final class MiddlewareResolver extends ProxyResolver
{
    private Closure $resolver;

    public function __construct(private readonly Closure $middle, private readonly array $pipes)
    {
        parent::__construct();
    }

    private function getResolveFunction(): Closure {
        if (!isset($this->resolver)) {
            $this->resolver = Middleware::create($this->pipes)->then($this->middle);
        }
        return $this->resolver;
    }

    protected function resolveToValue(mixed $typeData, array $arguments, GraphQlContext $context, ResolveInfo $info): mixed
    {
        return $this->getResolveFunction()($typeData, $arguments, $context, $info);
    }
}