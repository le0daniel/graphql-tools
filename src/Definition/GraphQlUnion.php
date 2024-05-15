<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Extending\ExtendUnion;
use GraphQlTools\Helper\OperationContext;
use GraphQlTools\Utility\Paths;

abstract class GraphQlUnion extends TypeDefinition
{
    /**
     * @var array<string, \Closure>
     */
    private array $extendedPossibleTypes = [];

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): UnionType {
        return new UnionType([
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
            'types' => fn() => array_map(fn(string $typeName) => $registry->type($typeName), $this->getAllPossibleTypeNames()),
            'resolveType' => function($_, OperationContext $context, $info) use ($registry) {
                $typeName = $context->cache->getCache(Paths::toString($info->path), $this->getName()) ?? $context->cache->setCache(
                    Paths::toString($info->path),
                    $this->getName(),
                    $this->resolveToTypeWithExtendedTypes($_, $context->context, $info)
                );

                return $registry->type($typeName);
            },
        ]);
    }

    /**
     * @param array<ExtendUnion> $extensions
     * @return $this
     */
    public function extendWith(array $extensions): static
    {
        $clone = clone $this;
        foreach ($extensions as $extension) {
            $clone->extendedPossibleTypes[$extension->getPossibleTypeName()] = $extension->getResolver();
        }
        return $clone;
    }

    private function resolveToTypeWithExtendedTypes(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string {
        foreach ($this->extendedPossibleTypes as $typeName => $resolver) {
            if ($resolver($typeValue, $context, $info)) {
                return $typeName;
            }
        }
        return $this->resolveToType($typeValue, $context, $info);
    }

    abstract public function resolveToType(mixed $typeValue, GraphQlContext $context, ResolveInfo $info): string;

    private function getAllPossibleTypeNames(): array
    {
        return [
            ...$this->possibleTypes(),
            ...array_keys($this->extendedPossibleTypes)
        ];
    }

    /**
     * Return an array of type name (or aliases). They will be resolved
     * by the type registry.
     * @return array<string>
     */
    abstract protected function possibleTypes(): array;

}
