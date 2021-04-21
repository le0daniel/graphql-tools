<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Context;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\Execution\OperationContext;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Strings;

abstract class GraphQlUnion extends UnionType {
    use HasDescription, ResolvesType;

    public function __construct(
        protected TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'types' => fn() => $this->possibleTypes(),
            ]
        );
    }

    /**
     * Return an array of all possible types for this union.
     *
     * Ex:
     * return [
     *     $this->typeRepository->type(MyType::class)
     * ];
     *
     * @return array<callable|Type>
     */
    abstract protected function possibleTypes(): array;

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Union')
            ? substr($typeName, 0, -strlen('Union'))
            : $typeName;
    }

}
