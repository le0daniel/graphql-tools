<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\IsWrapable;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Strings;

abstract class GraphQlUnion extends UnionType {
    use HasDescription, ResolvesType, IsWrapable;

    public function __construct(
        protected TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'types' => fn() => array_map(function($type){
                    return is_string($type)
                        ? $this->typeRepository->type($type)
                        : $type;
                }, $this->possibleTypes()),
            ]
        );
    }

    /**
     * Return an array of all possible types for this union.
     *
     * Ex:
     * return [
     *     $this->typeRepository->type(MyType::class),
     *     MyType::class,
     *     MyType::typeName() # For Lazy loaded types
     * ];
     *
     * @return array<callable|Type|string>
     */
    abstract protected function possibleTypes(): array;

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Union')
            ? substr($typeName, 0, -strlen('Union'))
            : $typeName;
    }

}
