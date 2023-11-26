<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Shared\HasFields;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Types;

abstract class GraphQlInterface extends TypeDefinition
{
    use HasFields;

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): InterfaceType
    {
        return new InterfaceType([
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'fields' => fn() => $this->initializeFields(
                $registry,
                [$this->fields(...), ...$this->mergedFieldFactories],
                $schemaRules,
            ),
            'resolveType' => function($_, OperationContext $context, $info) use ($registry) {
                $typeName = $context->getCache($info->path, $this->getName()) ?? $context->setCache(
                    $info->path,
                    $this->getName(),
                    $this->resolveToType($_, $context->context, $info)
                );

                return $registry->type($typeName);
            },
        ]);
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }
}
