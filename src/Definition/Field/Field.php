<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQlTools\Helper\ProxyResolver;

final class Field extends GraphQlField
{
    /** @var callable|null */
    protected $resolveFunction = null;

    /** fn(mixed $data, array $arguments, Context $context, ResolveInfo $resolveInfo) => notNull */
    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }

    protected function getResolver(): ProxyResolver
    {
        return new ProxyResolver($this->resolveFunction
            ? fn($data, $arguments, $context, $info) => ($this->resolveFunction)($data, $this->validateArguments($arguments), $context, $info)
            : null,
        );
    }
}