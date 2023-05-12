<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use RuntimeException;

class FactoryTypeRegistry implements TypeRegistryContract
{
    private array $typeInstances = [];

    /**
     * @param array<string, callable|class-string> $types
     * @param array<string, string> $aliasesOfTypes
     * @param array<string, array<ExtendGraphQlType|class-string|Closure>> $extendedTypes
     */
    public function __construct(
        private readonly array $types,
        private readonly array $aliasesOfTypes = [],
        private readonly array $extendedTypes = [],
        private readonly array $tagsToExclude = [],
    )
    {
    }

    public function verifyAliasCollisions(): void {
        foreach ($this->aliasesOfTypes as $alias => $typeName) {
            if (array_key_exists($alias, $this->types)) {
                throw new RuntimeException("The alias `{$alias}` is used also as typename, which is invalid.");
            }
        }
    }

    public function type(string $nameOrAlias): Closure
    {
        return fn() => $this->getType(
            $this->resolveAliasToName($nameOrAlias)
        );
    }

    protected function resolveAliasToName(string $nameOrAlias): string
    {
        return $this->aliasesOfTypes[$nameOrAlias] ?? $nameOrAlias;
    }

    protected function getType(string $typeName): Type
    {
        return $this->typeInstances[$typeName] ??= $this->createInstanceOfType($typeName);
    }

    private function getTypeFieldExtensions(GraphQlInterface|GraphQlType $type): array {
        $factories = $this->getFieldExtensionsForTypeName($type->getName());

        if ($type instanceof GraphQlType) {
            foreach ($type->getInterfaces() as $interfaceNameOrAlias) {
                $factories = [
                    ...$factories,
                    ...$this->getFieldExtensionsForTypeName($this->resolveAliasToName($interfaceNameOrAlias))
                ];
            }
        }

        return $factories;
    }

    private function getFieldExtensionsForTypeName(string $typeName): array {
        if (!isset($this->extendedTypes[$typeName])) {
            return [];
        }

        $factories = [];
        /** @var class-string<ExtendGraphQlType>|Closure|ExtendGraphQlType $extension */
        foreach ($this->extendedTypes[$typeName] as $extension) {
            $factories[] = match (true) {
                is_string($extension) => (new $extension)->getFields(...),
                $extension instanceof ExtendGraphQlType => $extension->getFields(...),
                $extension instanceof Closure => $extension,
                default => throw new DefinitionException("Invalid type field extension given. Expected Closure or class-string<ExtendGraphQlType>."),
            };
        }

        return $factories;
    }

    protected function createInstanceOfType(string $typeName): Type
    {
        $typeFactory = $this->types[$typeName] ?? null;
        if (!$typeFactory) {
            throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?");
        }

        /** @var DefinesGraphQlType $instance */
        $instance = match (true) {
            is_string($typeFactory) => new $typeFactory,
            $typeFactory instanceof Closure => $typeFactory(),
            $typeFactory instanceof DefinesGraphQlType => $typeFactory,
            default => throw new DefinitionException("Invalid type factory provided for '{$typeName}'. Expected class-string, closure, or instance of DefinesGraphQlType got: " . gettype($typeFactory))
        };

        $hasFields = $instance instanceof GraphQlType || $instance instanceof GraphQlInterface;
        if (!$hasFields) {
            return $instance->toDefinition($this);
        }

        $extendedFields = $this->getTypeFieldExtensions($instance);
        return empty($extendedFields)
            ? $instance->toDefinition($this, $this->tagsToExclude)
            : $instance->mergeFieldFactories(...$extendedFields)->toDefinition($this, $this->tagsToExclude);
    }
}