<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendInterface;
use GraphQlTools\Definition\Shared\HasFields;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Paths;

abstract class GraphQlInterface extends TypeDefinition
{
    use HasFields;
    private array $typeResolvers = [];

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    final protected function resolveTypeWithExtendedTypeResolvers(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string
    {
        foreach ($this->typeResolvers as $typeResolver) {
            $typeName = $typeResolver($typeValue, $context, $info);
            if ($typeName !== null) {
                return $typeName;
            }
        }
        return $this->resolveToType($typeValue, $context, $info);
    }

    /**
     * @param array<ExtendInterface> $extensions
     * @return $this
     */
    public function extendWith(array $extensions): static {
        $clone = clone $this;

        foreach ($extensions as $extension) {
            if ($resolver = $extension->getResolver()) {
                $clone->typeResolvers[] = $resolver;
            }
        }

        return $clone;
    }


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
                $typeName = $context->cache->getCache(Paths::toString($info->path), $this->getName()) ?? $context->cache->setCache(
                    Paths::toString($info->path),
                    $this->getName(),
                    $this->resolveTypeWithExtendedTypeResolvers($_, $context->context, $info)
                );

                return $registry->type($typeName);
            },
        ]);
    }
}
