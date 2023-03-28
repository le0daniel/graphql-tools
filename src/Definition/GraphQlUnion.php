<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Classes;

abstract class GraphQlUnion implements DefinesGraphQlType
{
    use HasDescription, HasDeprecation;

    private const CLASS_POSTFIX = 'Union';

    public function toDefinition(TypeRegistry $registry): UnionType {
        return new UnionType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'types' => fn() => array_map(fn(string $typeName) => $registry->type($typeName), $this->possibleTypes()),
            'resolveFn' => [static::class, 'resolveToType'],
            'resolveType' => fn($_, OperationContext $context, $info) => $registry->type(
                static::resolveToType($_, $context->context, $info)
            ),
        ]);
    }

    abstract public static function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    public function getName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

    abstract protected function possibleTypes(): array;

}
