<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Helper\ProxyResolver;
use RuntimeException;

class Field extends GraphQlField
{
    /**
     * @var callable
     */
    protected $resolveFunction;

    /**
     * Callable fn(mixed $data, array $validatedArguments, Context $context, ResolveInfo $resolveInfo) => mixed
     *
     * @param Closure $resolveFunction
     * @return $this
     */
    public function resolvedBy(Closure $resolveFunction): self {
        $this->resolveFunction = $resolveFunction;
        return $this;
    }

    protected function getResolver(): ProxyResolver {
        if (!$this->resolveFunction) {
            return new ProxyResolver();
        }

        return new ProxyResolver(function($data, array $arguments, Context $context, ResolveInfo $info) {
            return ($this->resolveFunction)(
                $data,
                $this->validateArguments($arguments),
                $context,
                $info
            );
        });
    }
}