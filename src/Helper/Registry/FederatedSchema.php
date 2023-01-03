<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Helper\TypeCacheManager;
use GraphQlTools\Utility\Arrays;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class FederatedSchema
{
    private array $types = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];

    public function registerType(string $typeName, string $declarationClassName, bool $eagerlyLoad = false): void {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }

        $this->types[$typeName] = $declarationClassName;

        if ($eagerlyLoad) {
            $this->eagerlyLoadedTypes[] = $typeName;
        }
    }

    public function registerTypes(array $types): void {
        foreach ($types as $typeName => $className) {
            $typeName = is_string($typeName) ? $typeName : $className::typeName();
            $this->registerType($typeName, $className);
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

    protected function createTypeAndAliases(): array {
        $types = $this->types;
        $aliases = array_flip($types);
        $fieldExtensions = $this->resolveFieldExtensionAliases($aliases);
        foreach ($fieldExtensions as $typeName => $extensionFactories) {
            if (!isset($types[$typeName])) {
                throw new RuntimeException("Tried to extend type '{$typeName}' which has not been registered.");
            }

            $typeClassName = $types[$typeName];
            $types[$typeName] = static function (TypeRegistryContract $registry) use ($typeClassName, $extensionFactories) {
                /** @var GraphQlType|GraphQlInterface $instance */
                $instance = new $typeClassName;
                return $instance->toDefinition($registry, $extensionFactories);
            };
        }

        return [$types, $aliases];
    }

    protected function createInstanceOfTypeRegistry(array $types, array $aliases): TypeRegistryContract
    {
        return new FactoryTypeRegistry(
            $types,
            $aliases
        );
    }

    public function cacheSchema(): string {
        $cacheManager = new TypeCacheManager(lazyFields: true);
        [$aliases, $types] = $cacheManager->cache(
            array_values($this->types),
            $this->typeFieldExtensions
        );
        $exportedAliases = var_export($aliases, true);
        $mappedTypes = Arrays::mapWithKeys($types,fn(string $typeName, string $code): array => [
            $typeName, var_export($typeName, true) . " => {$code}",
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
                    'typeLoader' => $registry->eagerlyLoadType(...),
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
        [$types, $aliases] = $this->createTypeAndAliases();
        $typeRegistry = $this->createInstanceOfTypeRegistry($types, $aliases);
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
                    'typeLoader' => $typeRegistry->eagerlyLoadType(...),
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

}