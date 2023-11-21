<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Types;

abstract class GraphQlUnion extends TypeDefinition
{

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): UnionType {
        return new UnionType([
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'types' => fn() => array_map(fn(string $typeName) => $registry->type($typeName), $this->possibleTypes()),
            'resolveType' => fn($_, OperationContext $context, $info) => $registry->type(
                $this->resolveToType($_, $context->context, $info)
            ),
        ]);
    }

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    abstract protected function possibleTypes(): array;

}
