<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Data\ValueObjects\RawPhpExpression;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\TypeCacheManager;
use GraphQlTools\Utility\Compiling;
use GraphQlTools\Utility\Types;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class FederatedSchema
{
    private array $types = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];

    public function register(DefinesGraphQlType $definition): void
    {
        $this->verifyTypeNameIsUsed($definition->getName());
        $this->types[$definition->getName()] = $definition;
    }

    public function registerType(string $typeName, string|DefinesGraphQlType $typeDeclaration): void
    {
        $this->verifyTypeNameIsUsed($typeName);
        $this->types[$typeName] = $typeDeclaration;
    }

    private function verifyTypeNameIsUsed(string $typeName): void
    {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    public function registerTypes(array $types): void
    {
        foreach ($types as $possibleName => $declaration) {
            $typeName = match (true) {
                is_string($possibleName) => $possibleName,
                is_string($declaration) => $declaration::typeName(),
                $declaration instanceof DefinesGraphQlType => $declaration->getName(),
                default => throw new DefinitionException('Expected the type name to be resolvable, could not resolve name.'),
            };

            $this->registerType($typeName, $declaration);
        }
    }

    public function registerEagerlyLoadedType(string $typeNameOrAlias): void
    {
        $this->eagerlyLoadedTypes[] = $typeNameOrAlias;
    }

    /**
     * @param string $typeOrClassName
     * @param Closure(TypeRegistryContract): array $fieldFactory
     * @return void
     */
    public function extendType(string $typeOrClassName, Closure $fieldFactory): void
    {
        if (!isset($this->typeFieldExtensions[$typeOrClassName])) {
            $this->typeFieldExtensions[$typeOrClassName] = [];
        }
        $this->typeFieldExtensions[$typeOrClassName][] = $fieldFactory;
    }

    protected function resolveFieldExtensionAliases(array $aliases): array
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

    protected function getAliases(): array {
        $aliases = [];
        foreach ($this->types as $typeName => $declaration) {
            if (is_string($declaration)) {
                $aliases[$declaration] = $typeName;
            }
        }
        return $aliases;
    }

    protected function createAliasesAndExtensions(): array
    {
        $aliases = $this->getAliases();
        $fieldExtensions = $this->resolveFieldExtensionAliases($aliases);
        return [$aliases, $fieldExtensions];
    }

    protected function combineFieldExtensionsAndTypes(array $types, array $fieldExtensions): array
    {
        foreach ($fieldExtensions as $typeName => $extensionFactories) {
            if (!isset($types[$typeName])) {
                throw new DefinitionException("Tried to extend type '{$typeName}' which has not been registered.");
            }

            $typeClassName = $types[$typeName];
            $types[$typeName] = static function (TypeRegistryContract $registry) use ($typeClassName, $extensionFactories) {
                /** @var GraphQlType|GraphQlInterface $instance */
                $instance = $typeClassName instanceof DefinesGraphQlType ? $typeClassName : new $typeClassName;
                return $instance->toDefinition($registry, $extensionFactories);
            };
        }
        return $types;
    }

    public function cacheSchema(): string
    {
        $cacheManager = new TypeCacheManager();
        [$aliases, $fieldExtensions] = $this->createAliasesAndExtensions();
        [$types, $dependencies] = $cacheManager->cache($this->types, $aliases, $fieldExtensions);

        $eagerlyLoadedTypes = array_map(fn(string $nameOrAlias) => $aliases[$nameOrAlias] ?? $nameOrAlias, $this->eagerlyLoadedTypes);

        $export = Compiling::exportArray([
            'eagerlyLoaded' => $eagerlyLoadedTypes,
            'aliases' => $aliases,
            'types' => array_map(fn(string $code): RawPhpExpression => new RawPhpExpression($code), $types),
        ]);

        return "       
            return {$export};
        ";
    }

    public static function fromCachedSchema(array $cache, string $queryTypeName, ?string $mutationTypeName = null): Schema
    {
        return self::toSchema(
            new FactoryTypeRegistry($cache['types'], $cache['aliases']),
            $queryTypeName,
            $mutationTypeName,
            true,
            $cache['eagerlyLoaded'] ?? []
        );
    }

    public function createSchema(
        string  $queryTypeName,
        ?string $mutationTypeName = null,
        bool    $assumeValid = true,
    ): Schema
    {
        [$aliases, $fieldExtensions] = $this->createAliasesAndExtensions();
        $typeRegistry = new FactoryTypeRegistry(
            $this->combineFieldExtensionsAndTypes($this->types, $fieldExtensions),
            $aliases
        );
        return self::toSchema(
            $typeRegistry,
            $queryTypeName,
            $mutationTypeName,
            $assumeValid,
            $this->eagerlyLoadedTypes
        );
    }

    private static function toSchema(
        TypeRegistryContract $registry,
        string               $queryTypeName,
        ?string              $mutationTypeName = null,
        bool                 $assumeValid = true,
        array                $eagerlyLoadedTypes = []
    ): Schema
    {
        return new Schema(
            SchemaConfig::create(
                [
                    'query' => Schema::resolveType($registry->type($queryTypeName)),
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
                        } catch (DefinitionException $exception) {
                            if (Types::isDefaultOperationTypeName($typeNameOrClassName)) {
                                return null;
                            }
                            throw $exception;
                        }
                    },
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

}