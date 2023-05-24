<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Definition\Shared\MergesFields;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Types;

abstract class GraphQlInterface implements DefinesGraphQlType
{
    use InitializesFields, HasDescription, HasDeprecation, MergesFields;
    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): InterfaceType
    {
        return new InterfaceType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'fields' => fn() => $this->initializeFields(
                $registry,
                [$this->fields(...), ...$this->mergedFieldFactories],
                $schemaRules,
            ),
            'resolveType' => fn($_, OperationContext $context, $info) => $registry->type(
                $this->resolveToType($_, $context->context, $info)
            ),
        ]);
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;
}
