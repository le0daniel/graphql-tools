<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

interface TypeRegistry
{
    /**
     * Given a type name or class name, return an instance of the Type or a Closure which resolves
     * to a type
     *
     * @param string|class-string<Type>  $classOrTypeName
     * @return Closure(): Type|Type
     */
    public function type(string $classOrTypeName): Closure|Type;

    /**
     * Given a type name or class name, return an instance of the Type
     * to a type
     *
     * @param string|class-string<Type> $classOrTypeName
     * @return Type
     */
    public function eagerlyLoadType(string $classOrTypeName): Type;
}