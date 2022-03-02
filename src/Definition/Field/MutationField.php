<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Context;
use GraphQlTools\Helper\ProxyResolver;

class MutationField extends Field
{

    protected function getResolver(): ProxyResolver
    {
        if (!$this->resolveFunction) {
            throw new \RuntimeException("Every mutation field MUST have a defined resolver");
        }

        return new ProxyResolver(function (mixed $data, array $arguments, Context $context, ResolveInfo $info): mixed {
            return $context->executeMutationResolveFunction(
                $this->resolveFunction,
                $data, $this->validateArguments($arguments), $info
            );
        });
    }

}