<?php

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\Context;

trait ResolvesType
{

    /**
     * Resolve a given Data Object to the correct Type. Return a class or type name.
     *
     * @param mixed $typeValue
     * @param GraphQlContext $context
     * @param ResolveInfo $info
     * @return string
     */
    abstract protected function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    final public function resolveType($typeValue, $context, ResolveInfo $info): Type
    {
        $typeOrClassName = $this->resolveToType($typeValue, $context->context, $info);
        return $this->typeRegistry->eagerlyLoadType($typeOrClassName);
    }

}
