<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Utility\Types;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class FederatedSchema
{
    /**
     * @var array<string,DefinesGraphQlType|string>
     */
    private array $types = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];

    public function register(DefinesGraphQlType|string $definition): void
    {
        $typeName = is_string($definition)
            ? Types::inferNameFromClassName($definition)
            : $definition->getName();

        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $definition;
    }

    public function verifyTypeNames(): void
    {
        foreach ($this->types as $name => $definition) {
            $realTypeName = $definition instanceof DefinesGraphQlType
                ? $definition->getName()
                : (new $definition)->getName();

            if ($name !== $realTypeName) {
                throw new DefinitionException("The registered name `{$name}` does not match the name of the type `{$realTypeName}`");
            }
        }
    }

    public function registerType(string $typeName, string|DefinesGraphQlType $typeDeclaration): void
    {
        if ($typeDeclaration instanceof DefinesGraphQlType) {
            $this->register($typeDeclaration);
            return;
        }

        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $typeDeclaration;
    }

    private function verifyTypeNameIsNotUsed(string $typeName): void
    {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    public function registerTypes(array $types): void
    {
        foreach ($types as $possibleName => $declaration) {
            is_string($possibleName)
                ? $this->registerType($possibleName, $declaration)
                : $this->register($declaration);
        }
    }

    public function registerEagerlyLoadedType(string $typeNameOrAlias): void
    {
        $this->eagerlyLoadedTypes[] = $typeNameOrAlias;
    }

    /**
     * @param string $typeNameOrAlias
     * @param Closure(TypeRegistryContract): array $fieldFactory
     * @return void
     */
    public function extendType(string $typeNameOrAlias, Closure $fieldFactory): void
    {
        if (!isset($this->typeFieldExtensions[$typeNameOrAlias])) {
            $this->typeFieldExtensions[$typeNameOrAlias] = [];
        }
        $this->typeFieldExtensions[$typeNameOrAlias][] = $fieldFactory;
    }

    protected function resolveFieldExtensions(array $aliases): array
    {
        $extensionFactories = [];

        foreach ($this->typeFieldExtensions as $typeNameOrAlias => $fieldExtensions) {
            $typeName = $aliases[$typeNameOrAlias] ?? $typeNameOrAlias;
            if (!isset($extensionFactories[$typeName])) {
                $extensionFactories[$typeName] = [];
            }
            array_push($extensionFactories[$typeName], ...$fieldExtensions);
        }

        return $extensionFactories;
    }

    protected function createAliases(): array
    {
        $aliases = [];
        foreach ($this->types as $typeName => $declaration) {
            if (is_string($declaration)) {
                $aliases[$declaration] = $typeName;
            }
        }
        return $aliases;
    }

    protected function createTypeFactories(array $types, array $aliases, array $excludeTags): array
    {
        $typeFactories = [];
        $fieldExtensions = $this->resolveFieldExtensions($aliases);

        foreach ($types as $name => $definition) {
            $fieldFactories = $fieldExtensions[$name] ?? null;
            $typeFactories[$name] = static function (TypeRegistryContract $typeRegistry) use ($definition, $fieldFactories, $excludeTags): Type {
                /** @var DefinesGraphQlType $instance */
                $instance = $definition instanceof DefinesGraphQlType ? $definition : new $definition;
                return $instance->toDefinition($typeRegistry, $fieldFactories ?? [], $excludeTags);
            };
        }

        return $typeFactories;
    }

    /**
     * @template T of Schema
     * @param string|null $queryTypeName
     * @param string|null $mutationTypeName
     * @param bool $assumeValid
     * @param array $excludeTags
     * @param class-string<T> $schemaClassName
     * @return Schema
     */
    public function createSchema(
        ?string $queryTypeName = null,
        ?string $mutationTypeName = null,
        bool    $assumeValid = true,
        array   $excludeTags = [],
        string  $schemaClassName = Schema::class,
    ): Schema
    {
        $aliases = $this->createAliases();
        $eagerlyLoadedTypes = $this->eagerlyLoadedTypes;
        $registry = new FactoryTypeRegistry(
            $this->createTypeFactories($this->types, $aliases, $excludeTags),
            $aliases
        );

        return new $schemaClassName(
            SchemaConfig::create(
                [
                    'query' => $queryTypeName
                        ? Schema::resolveType($registry->type($queryTypeName))
                        : null,
                    'mutation' => $mutationTypeName
                        ? Schema::resolveType($registry->type($mutationTypeName))
                        : null,
                    'types' => static function () use ($eagerlyLoadedTypes, $registry) {
                        return array_map(
                            fn(string $name) => Schema::resolveType($registry->type($name)),
                            $eagerlyLoadedTypes,
                        );
                    },
                    'typeLoader' => static function (string $typeNameOrClassName) use ($registry) {
                        try {
                            return Schema::resolveType($registry->type($typeNameOrClassName));
                        } catch (DefinitionException) {
                            return null;
                        }
                    },
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

}