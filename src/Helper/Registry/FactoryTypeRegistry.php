<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
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

    /**
     * @param array<string, class-string<DefinesGraphQlType>|DefinesGraphQlType> $types
     * @param array<string, string> $aliasesOfTypes
     * @param array<string, array<ExtendGraphQlType|class-string|Closure>> $typeExtensions
     */
    public function __construct(
        protected readonly array       $types,
        protected readonly array       $aliasesOfTypes = [],
        protected readonly array       $typeExtensions = [],
        protected readonly SchemaRules $schemaRules = new AllVisibleSchemaRule()
    )
    {
    }

    /**
     * @return void
     */
    public function verifyAliasCollisions(): void
    {
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
    protected function getAllExtendedFieldFactoriesFor(GraphQlInterface|GraphQlType $type): array
    {
        $factories = $this->getFieldExtensionsForTypeName($type->getName());

        if ($type instanceof GraphQlType) {
            foreach ($type->getInterfaces() as $interfaceNameOrAlias) {
                array_push($factories, ...$this->getFieldExtensionsForTypeName(
                    $this->resolveAliasToName($interfaceNameOrAlias)
                ));
            }
        }

        return $factories;
    }

    protected function getFieldExtensionsForTypeName(string $typeName): array
    {
        if (!isset($this->typeExtensions[$typeName])) {
            return [];
        }

        $factories = [];
        /** @var class-string<ExtendGraphQlType>|Closure|ExtendGraphQlType $extension */
        foreach ($this->typeExtensions[$typeName] as $extension) {
            $factories[] = match (true) {
                is_string($extension) => (new $extension)->getFields(...),
                $extension instanceof ExtendGraphQlType => $extension->getFields(...),
                $extension instanceof Closure => $extension,
                default => throw new DefinitionException("Invalid type field extension given. Expected Closure or class-string<ExtendGraphQlType>."),
            };
        }

        return $factories;
    }

    protected function createInstanceOfGraphQlType(string $typeName): DefinesGraphQlType
    {
        $typeFactory = $this->types[$typeName] ?? null;

        return match (true) {
            is_string($typeFactory) => new $typeFactory,
            $typeFactory instanceof DefinesGraphQlType => $typeFactory,
            is_null($typeFactory) => throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?"),
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
        $definesGraphQlType = $this->createInstanceOfGraphQlType($typeName);

        return match (true) {
            $definesGraphQlType instanceof GraphQlType, $definesGraphQlType instanceof GraphQlInterface => $this->extendTypeDefinitionWithExtendedFields($definesGraphQlType),
            default => $definesGraphQlType->toDefinition($this, $this->schemaRules)
        };
    }

    /**
     * @throws DefinitionException
     */
    protected function extendTypeDefinitionWithExtendedFields(GraphQlType|GraphQlInterface $type): Type
    {
        $extendedFields = $this->getAllExtendedFieldFactoriesFor($type);
        return empty($extendedFields)
            ? $type->toDefinition($this, $this->schemaRules)
            : $type
                ->mergeFieldFactories(...$extendedFields)
                ->toDefinition($this, $this->schemaRules);
    }

    public function nonNull(Type|Closure $wrappedType): NonNull
    {
        return new NonNull($wrappedType);
    }

    public function listOf(Type|Closure $wrappedType): ListOfType
    {
        return new ListOfType($wrappedType);
    }

    public function int(): ScalarType
    {
        return Type::int();
    }

    public function float(): ScalarType
    {
        return Type::float();
    }

    public function string(): ScalarType
    {
        return Type::string();
    }

    public function id(): ScalarType
    {
        return Type::id();
    }

    public function boolean(): ScalarType
    {
        return Type::boolean();
    }
}