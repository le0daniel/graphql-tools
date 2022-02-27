<?php

namespace GraphQlTools\Definition\Shared;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Context;
use GraphQlTools\Helper\OperationContext;

trait ResolvesType {

    /**
     * This function is responsible to return the correct type given it's data.
     * It is possible to return a className or typeName to make it resolved by the
     * type repository
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
    abstract protected function resolveToType(mixed $typeValue, Context $context, ResolveInfo $info): callable|Type|string;

    final public function resolveType($typeValue, $context, ResolveInfo $info): Type|callable {
        $type = $this->resolveToType($typeValue, $context->context, $info);
        return $type instanceof Type
            ? $type
            : $this->typeRepository->type($type);
    }

}
