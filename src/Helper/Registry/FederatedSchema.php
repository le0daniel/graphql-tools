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
    private array $typeResolutionMap = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];

    public function registerType(string $typeName, string $declarationClassName, bool $eagerlyLoad = false): void {
        if (isset($this->typeResolutionMap[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered");
        }

        $this->typeResolutionMap[$typeName] = $declarationClassName;

        if ($eagerlyLoad) {
            $this->eagerlyLoadedTypes[] = $typeName;
        }
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

    protected function createInstanceOfTypeRegistry(): TypeRegistryContract
    {
        $typeMap = $this->typeResolutionMap;
        $reverseResolutionMap = array_flip($this->typeResolutionMap);

        // Normalize Type Extensions to type Name
        foreach ($this->createFieldExtensionList($reverseResolutionMap) as $typeName => $fieldExtensions) {
            if (!isset($typeMap[$typeName])) {
                throw new RuntimeException("Tried to extend type '{$typeName}' which has not been registered.");
            }

            $typeClassName = $typeMap[$typeName];
            $typeMap[$typeName] = static function (TypeRegistryContract $registry) use ($typeClassName, $fieldExtensions) {
                /** @var GraphQlType|GraphQlInterface $instance */
                $instance = new $typeClassName;
                return $instance->toDefinition($registry, $fieldExtensions);
            };
        }

        return new FactoryTypeRegistry(
            $typeMap,
            $reverseResolutionMap
        );
    }

    private function createFieldExtensionList(array $reverseResolutionMap): array {
        $extensions = [];
        foreach ($this->typeFieldExtensions as $typeNameOfClassName => $fieldExtensions) {
            $typeName = $reverseResolutionMap[$typeNameOfClassName] ?? $typeNameOfClassName;

            if (!isset($extensions[$typeName])) {
                $extensions[$typeName] = $fieldExtensions;
            }
            else {
                array_push($extensions[$typeName], ...$fieldExtensions);
            }
        }
        return $extensions;
    }

    public function cacheSchema(): string {
        $cacheManager = new TypeCacheManager(lazyFields: true);
        [$aliases, $types] = $cacheManager->cache(
            array_values($this->typeResolutionMap),
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
        $typeRegistry = $this->createInstanceOfTypeRegistry();

        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $typeRegistry->eagerlyLoadType($queryTypeName),
                    'mutation' => $mutationTypeName
                        ? $typeRegistry->eagerlyLoadType($mutationTypeName)
                        : null,
                    'types' => fn() => array_map(
                        $typeRegistry->eagerlyLoadType(...),
                        $this->eagerlyLoadedTypes,
                    ),
                    'typeLoader' => $typeRegistry->eagerlyLoadType(...),
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

}