<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use RuntimeException;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;

class FactoryTypeRegistry implements TypeRegistryContract
{
    protected array $typeInstances = [];
    protected readonly SchemaRules $schemaRules;

    /**
     * @param array<string, callable|class-string|DefinesGraphQlType> $types
     * @param array<string, string> $aliasesOfTypes
     * @param array<string, array<ExtendGraphQlType|class-string|Closure>> $extendedTypes
     */
    public function __construct(
        protected readonly array $types,
        protected readonly array $aliasesOfTypes = [],
        protected readonly array $extendedTypes = [],
        ?SchemaRules $schemaRules = null
    )
    {
        $this->schemaRules = $schemaRules ?? new AllVisibleSchemaRule();
    }

    public function verifyAliasCollisions(): void {
        foreach ($this->aliasesOfTypes as $alias => $typeName) {
            if (array_key_exists($alias, $this->types)) {
                throw new RuntimeException("The alias `{$alias}` is used also as typename, which is invalid.");
            }
        }
    }

    public function type(string $nameOrAlias, ?GraphQlTypes $typeHint = null): Closure
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
        return $this->typeInstances[$typeName] ??= $this->createType($typeName);
    }

    /**
     * @throws DefinitionException
     */
    protected function getTypeFieldExtensions(GraphQlInterface|GraphQlType $type): array {
        $factories = $this->getFieldExtensionsForTypeName($type->getName());

        if ($type instanceof GraphQlType) {
            foreach ($type->getInterfaces() as $interfaceNameOrAlias) {
                $factories = [
                    ...$factories,
                    ...$this->getFieldExtensionsForTypeName(
                        $this->resolveAliasToName($interfaceNameOrAlias)
                    )
                ];
            }
        }

        return $factories;
    }

    protected function getFieldExtensionsForTypeName(string $typeName): array {
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

    protected function createInstanceOfGraphQlType(string $typeName): DefinesGraphQlType {
        $typeFactory = $this->types[$typeName] ?? null;
        if (!$typeFactory) {
            throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?");
        }

        return match (true) {
            is_string($typeFactory) => new $typeFactory,
            $typeFactory instanceof Closure => $typeFactory(),
            $typeFactory instanceof DefinesGraphQlType => $typeFactory,
            default => throw new DefinitionException("Invalid type factory provided for '{$typeName}'. Expected class-string, closure, or instance of DefinesGraphQlType got: " . gettype($typeFactory))
        };
    }

    /**
     * @param string $typeName
     * @return Type
     * @throws DefinitionException
     */
    protected function createType(string $typeName): Type
    {
        $instance = $this->createInstanceOfGraphQlType($typeName);

        $isExtendableType = $instance instanceof GraphQlType || $instance instanceof GraphQlInterface;
        if (!$isExtendableType) {
            return $instance->toDefinition($this, $this->schemaRules);
        }

        $extendedFields = $this->getTypeFieldExtensions($instance);
        return empty($extendedFields)
            ? $instance->toDefinition($this, $this->schemaRules)
            : $instance
                ->mergeFieldFactories(...$extendedFields)
                ->toDefinition($this,  $this->schemaRules);
    }
}