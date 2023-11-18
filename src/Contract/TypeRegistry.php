<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;

interface TypeRegistry
{
    /**
     * Given a type name or class name, return an instance of the Type or a Closure which resolves
     * to a type
     *
     * @param string $nameOrAlias
     * @param GraphQlTypes|null $typeHint
     * @return Closure(): Type|Type
     */
    public function type(string $nameOrAlias, ?GraphQlTypes $typeHint = null): Closure|Type;
    public function nonNull(Type|Closure $wrappedType): NonNull;
    public function listOf(Type|Closure $wrappedType): ListOfType;
    public function int(): ScalarType;
    public function float(): ScalarType;
    public function string(): ScalarType;
    public function id(): ScalarType;
    public function boolean(): ScalarType;
}