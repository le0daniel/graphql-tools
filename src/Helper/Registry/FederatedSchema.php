<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\TypeCacheManager;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Compiling;
use GraphQlTools\Utility\Types;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class FederatedSchema
{
    private array $types = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];

    public function register(DefinesGraphQlType $definition): void {
        $this->verifyTypeNameIsUsed($definition->getName());
        $this->types[$definition->getName()] = $definition;
    }

    public function registerType(string $typeName, string|DefinesGraphQlType $typeDeclaration): void {
        $this->verifyTypeNameIsUsed($typeName);
        $this->types[$typeName] = $typeDeclaration;
    }

    private function verifyTypeNameIsUsed(string $typeName): void {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    public function registerTypes(array $types): void {
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

    public function registerEagerlyLoadedType(string $typeNameOrAlias): void {
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

    protected function createTypeAndAliasesAndFieldExtensions(): array {
        $aliases = [];
        foreach ($this->types as $typeName => $declaration) {
            if (is_string($declaration)) {
                $aliases[$declaration] = $typeName;
            }
        }

        $types = $this->types;
        $fieldExtensions = $this->resolveFieldExtensionAliases($aliases);
        return [$types, $aliases, $fieldExtensions];
    }

    protected function combineFieldExtensionsAndTypes(array $types, array $fieldExtensions): array {
        foreach ($fieldExtensions as $typeName => $extensionFactories) {
            if (!isset($types[$typeName])) {
                throw new DefinitionException("Tried to extend type '{$typeName}' which has not been registered.");
            }

            $typeClassName = $types[$typeName];
            $types[$typeName] = static function (TypeRegistryContract $registry) use ($typeClassName, $extensionFactories) {
                /** @var GraphQlType|GraphQlInterface $instance */
                $instance = new $typeClassName;
                return $instance->toDefinition($registry, $extensionFactories);
            };
        }
        return $types;
    }

    protected function createInstanceOfTypeRegistry(array $types, array $aliases): TypeRegistryContract
    {
        return new FactoryTypeRegistry(
            $types,
            $aliases
        );
    }

    public function cacheSchema(): string {
        $cacheManager = new TypeCacheManager();
        [$types, $aliases, $fieldExtensions] = $this->createTypeAndAliasesAndFieldExtensions();
        [$types, $dependencies] = $cacheManager->cache($types, $aliases, $fieldExtensions);

        $exportedAliases = var_export($aliases, true);
        $mappedTypes = Arrays::mapWithKeys($types,fn(string $typeName, string $code): array => [
            $typeName, Compiling::exportVariable($typeName) . " => {$code}",
        ]);
        $typesCode = implode(','.PHP_EOL, $mappedTypes);
        $eagerlyLoadTypes = var_export($this->eagerlyLoadedTypes, true);

        return "       
            return [
                'eagerlyLoaded' => {$eagerlyLoadTypes},
                'aliases' => {$exportedAliases},
                'types' => [
                    {$typesCode}
                ]       
            ];
        ";
    }

    public static function fromCachedSchema(array $cache, string $queryTypeName, ?string $mutationTypeName = null): Schema {
        $registry = new FactoryTypeRegistry(
            $cache['types'],
            $cache['aliases']
        );

        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $registry->eagerlyLoadType($queryTypeName),
                    'mutation' => $mutationTypeName ? $registry->eagerlyLoadType($mutationTypeName) : null,
                    'typeLoader' => static function(string $typeNameOrClassName) use ($registry) {
                        try {
                            return $registry->eagerlyLoadType($typeNameOrClassName);
                        } catch (DefinitionException $exception) {
                            if (Types::isDefaultOperationTypeName($typeNameOrClassName)) {
                                return null;
                            }
                            throw $exception;
                        }
                    },
                    'types' => fn() => array_map(
                        $registry->eagerlyLoadType(...),
                        $cache['eagerlyLoaded'],
                    ),
                    'assumeValid' => true,
                ]
            )
        );
    }

    public function createSchema(
        string $queryTypeName,
        ?string $mutationTypeName = null,
        bool $assumeValid = true,
    ): Schema {
        [$types, $aliases, $fieldExtensions] = $this->createTypeAndAliasesAndFieldExtensions();
        $typeRegistry = $this->createInstanceOfTypeRegistry(
            $this->combineFieldExtensionsAndTypes($types, $fieldExtensions),
            $aliases
        );
        $eagerlyLoadedTypes = $this->eagerlyLoadedTypes;

        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $typeRegistry->eagerlyLoadType($queryTypeName),
                    'mutation' => $mutationTypeName
                        ? $typeRegistry->eagerlyLoadType($mutationTypeName)
                        : null,
                    'types' => static function() use ($eagerlyLoadedTypes, $typeRegistry) {
                        return array_map(
                            $typeRegistry->eagerlyLoadType(...),
                            $eagerlyLoadedTypes,
                        );
                    },
                    'typeLoader' => static function(string $typeNameOrClassName) use ($typeRegistry) {
                        try {
                            return $typeRegistry->eagerlyLoadType($typeNameOrClassName);
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