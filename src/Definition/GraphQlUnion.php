<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\OperationContext;

abstract class GraphQlUnion extends TypeDefinition
{
    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): UnionType {
        return new UnionType([
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'types' => fn() => array_map(fn(string $typeName) => $registry->type($typeName), $this->possibleTypes()),
            'resolveType' => function($_, OperationContext $context, $info) use ($registry) {
                $typeName = $context->cache->getCache($info->path, $this->getName()) ?? $context->cache->setCache(
                    $info->path,
                    $this->getName(),
                    $this->resolveToType($_, $context->context, $info)
                );

                return $registry->type($typeName);
            },
        ]);
    }

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    /**
     * Return an array of type name (or aliases). They will be resolved
     * by the type registry.
     * @return array<string>
     */
    abstract protected function possibleTypes(): array;

}
