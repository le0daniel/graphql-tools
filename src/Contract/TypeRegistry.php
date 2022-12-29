<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;

interface TypeRegistry
{
    /**
     * Given a Field, return a boolean to determine if this field should be visible or not
     * **Important**: does not work with lazy fields.
     *
     * @param Field $field
     * @return bool
     */
    public function shouldHideField(Field $field): bool;

    /**
     * Given an InputField, return a boolean to determine if this field should be visible or not
     *
     * @param InputField $inputField
     * @return bool
     */
    public function shouldHideInputField(InputField $inputField): bool;

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