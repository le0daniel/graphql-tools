<?php

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Execution\OperationContext;

trait ResolvesType {

    /**
     * This function is responsible to return the correct type given it's data.
     *
     * Ex:
     * switch($typeValue->type) {
     *     case 'special_key':
     *         return $this->typeRepository->type(MyType::class);
     * }
     *
     * @param mixed $typeValue
     * @param Context $context
     * @param ResolveInfo $info
     * @return callable|Type
     */
    abstract protected function resolveToType(mixed $typeValue, Context $context, ResolveInfo $info): callable|Type;

    /**
     * Delegates to the type resolve method.
     *
     * @param object $objectValue
     * @param OperationContext $context
     * @param ResolveInfo $info
     * @return callable|null
     */
    final public function resolveType($objectValue, $context, ResolveInfo $info): Type|callable {
        return $this->resolveToType($objectValue, $context->context, $info);
    }

}
