<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Helper\ProxyResolver;
use RuntimeException;

final class MutationField extends GraphQlField
{
    /** @var callable */
    private $resolveFunction;

    public function resolvedBy(Closure $callable): self {
        $this->resolveFunction = $callable;
        return $this;
    }

    protected function getResolver(): ProxyResolver
    {
        if (!$this->resolveFunction) {
            throw new RuntimeException("Every mutation field MUST have a defined resolve function (use 'resolvedBy' to declare it).");
        }

        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $info): mixed {
            return $context->executeMutationResolveFunction(
                $this->resolveFunction,
                $data, $this->validateArguments($arguments), $info
            );
        });
    }

}