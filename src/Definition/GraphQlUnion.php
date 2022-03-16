<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Definition\Shared\DefinesTypes;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Classes;

abstract class GraphQlUnion extends UnionType
{
    use HasDescription, ResolvesType, DefinesTypes;

    private const CLASS_POSTFIX = 'Union';

    final public function __construct(
        private TypeRepository $typeRepository
    )
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'types' => fn() => $this->initTypes($this->possibleTypes()),
            ]
        );
    }

    /**
     * Return an array of all possible types for this union.
     *
     * Ex:
     * return [
     *     MyType::class,
     *     fn(TypeRepository $typeRepository) => $typeRepository->type(MyType::class)
     * ];
     *
     * @return array<callable|Type|string>
     */
    abstract protected function possibleTypes(): array;

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
