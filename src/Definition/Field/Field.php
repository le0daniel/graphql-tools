<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

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
     * @param callable $resolveFunction
     * @return $this
     */
    public function resolvedBy(callable $resolveFunction): self {
        if ($resolveFunction instanceof ProxyResolver) {
            throw new RuntimeException("Invalid resolve function given. Expected callable, got proxy resolver.");
        }

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