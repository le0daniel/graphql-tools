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
use GraphQlTools\Contract\ExtendType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use RuntimeException;
use GraphQlTools\Data\ValueObjects\GraphQlTypes;

/**
 * This is an internal class providing functionality when using the schema registry.
 * This class should never be used on its own.
 * @internal
 */
class FactoryTypeRegistry implements TypeRegistryContract
{
    protected array $typeInstances = [];
    /**
     * @param array<string, class-string<DefinesGraphQlType>|DefinesGraphQlType> $typeFactories
     * @param array<string, string> $aliasesOfTypes
     * @param array<string, array<ExtendType|class-string<ExtendType>> $typeExtensions
     */
    public function __construct(
        protected readonly array       $typeFactories,
        protected readonly array       $aliasesOfTypes = [],
        protected readonly array       $typeExtensions = [],
        protected readonly SchemaRules $schemaRules = new AllVisibleSchemaRule()
    )
    {}

    /**
     * @return void
     */
    public function verifyAliasCollisions(): void
    {
        foreach ($this->aliasesOfTypes as $alias => $typeName) {
            if (array_key_exists($alias, $this->typeFactories)) {
                throw new RuntimeException("The alias `{$alias}` is used also as typename, which is invalid.");
            }
        }
    }

    public function type(string $nameOrAlias, ?GraphQlTypes $typeHint = null): Closure
    {
        return fn() => $this->getOrCreateType(
            $this->resolveAliasToName($nameOrAlias)
        );
    }

    protected function resolveAliasToName(string $nameOrAlias): string
    {
        return $this->aliasesOfTypes[$nameOrAlias] ?? $nameOrAlias;
    }

    protected function getOrCreateType(string $typeName): Type
    {
        return $this->typeInstances[$typeName] ??= $this->createType($typeName);
    }

    /**
     * @param string $typeName
     * @return Type
     * @throws DefinitionException
     */
    protected function createType(string $typeName): Type
    {
        // We create the built-in types separately, as they can not be customized for now.
        if (in_array($typeName, Type::BUILT_IN_TYPE_NAMES, true)) {
            return Type::builtInTypes()[$typeName];
        }

        $typeFactory = $this->typeFactories[$typeName] ?? null;

        $definition = match (true) {
            is_string($typeFactory) => new $typeFactory,
            $typeFactory instanceof DefinesGraphQlType => $typeFactory,
            is_null($typeFactory) => throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?"),
            default => throw new DefinitionException("Invalid type factory provided for '{$typeName}'. Expected class-string, closure, or instance of DefinesGraphQlType got: " . gettype($typeFactory))
        };

        return ($definition instanceof GraphQlType || $definition instanceof GraphQlInterface)
            ? $this->extendTypeDefinitionWithExtendedFields($definition)
            : $definition->toDefinition($this, $this->schemaRules);
    }

    /**
     * @throws DefinitionException
     */
    protected function extendTypeDefinitionWithExtendedFields(GraphQlType|GraphQlInterface $type): Type
    {
        $extendedFields = $this->getFieldExtensionsByTypeName($type->getName());
        if ($type instanceof GraphQlType) {
            foreach ($type->getInterfaces() as $interfaceNameOrAlias) {
                array_push($extendedFields, ...$this->getFieldExtensionsByTypeName(
                    $this->resolveAliasToName($interfaceNameOrAlias)
                ));
            }
        }

        return empty($extendedFields)
            ? $type->toDefinition($this, $this->schemaRules)
            : $type->mergeFieldFactories(...$extendedFields)
                ->toDefinition($this, $this->schemaRules);
    }

    protected function getFieldExtensionsByTypeName(string $typeName): array
    {
        if (!isset($this->typeExtensions[$typeName])) {
            return [];
        }

        $factories = [];
        /** @var class-string<ExtendType>|ExtendType $extension */
        foreach ($this->typeExtensions[$typeName] as $extension) {
            $factories[] = match (true) {
                is_string($extension) => (new $extension)->getFields(...),
                $extension instanceof ExtendType => $extension->getFields(...),
                default => throw new DefinitionException("Invalid type field extension given. Expected Closure or class-string<ExtendGraphQlType>."),
            };
        }

        return $factories;
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
        /** @var ScalarType */
        return $this->getOrCreateType(Type::INT);
    }

    public function float(): ScalarType
    {
        /** @var ScalarType */
        return $this->getOrCreateType(Type::FLOAT);
    }

    public function string(): ScalarType
    {
        /** @var ScalarType */
        return $this->getOrCreateType(Type::STRING);
    }

    public function id(): ScalarType
    {
        /** @var ScalarType */
        return $this->getOrCreateType(Type::ID);
    }

    public function boolean(): ScalarType
    {
        /** @var ScalarType */
        return $this->getOrCreateType(Type::BOOLEAN);
    }
}