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
use GraphQlTools\Contract\ExtendsGraphQlDefinition;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
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
     * @param array<string, array<ExtendsGraphQlDefinition|class-string<ExtendsGraphQlDefinition>> $extensions
     */
    public function __construct(
        protected readonly array       $typeFactories,
        protected readonly array       $aliasesOfTypes = [],
        protected readonly array       $extensions = [],
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
            null === $typeFactory => throw new DefinitionException("Could not resolve type '{$typeName}', no factory provided. Did you register this type?"),
            default => throw new DefinitionException("Invalid type factory provided for '{$typeName}'. Expected class-string, closure, or instance of DefinesGraphQlType got: " . gettype($typeFactory))
        };

        $extended = match (true) {
            $definition instanceof GraphQlType, $definition instanceof GraphQlInterface, $definition instanceof GraphQlUnion => $this->applyExtensions($definition),
            default => $definition
        };

        return $extended->toDefinition($this, $this->schemaRules);
    }

    protected function applyExtensions(DefinesGraphQlType $type): DefinesGraphQlType
    {
        $extensions = $this->getAllExtensionsFor($type->getName());

        if (empty($extensions)) {
            return $type;
        }

        return match(true) {
            $type instanceof GraphQlType => $type->extendWith($extensions),
            $type instanceof GraphQlInterface => $type->extendWith($extensions),
            $type instanceof GraphQlUnion => $type->extendWith($extensions),
            default => throw new DefinitionException("Invalid type extension given. Expected GraphQlType or GraphQlInterface got: " . get_class($type))
        };
    }

    protected function getAllExtensionsFor(string $typeName): array
    {
        $extensions = [];
        /** @var class-string<ExtendsGraphQlDefinition>|ExtendsGraphQlDefinition $extension */
        foreach (($this->extensions[$typeName] ?? []) as $extension) {
            $extensions[] = match (true) {
                is_string($extension) => (new $extension),
                $extension instanceof ExtendsGraphQlDefinition => $extension,
                default => throw new DefinitionException("Invalid type field extension given. Expected Closure or class-string<ExtendGraphQlType>."),
            };
        }

        return $extensions;
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