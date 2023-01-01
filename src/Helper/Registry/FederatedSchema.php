<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class FederatedSchema
{
    private array $typeResolutionMap = [];
    private array $eagerlyLoadedTypes = [];
    private array $typeFieldExtensions = [];
    private array $directives = [];

    public function registerDirective($directive): void {
        $this->directives[] = $directive;
    }

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

        return new ClassBasedTypeRegistry(
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

    public function createSchema(
        string $queryTypeName,
        ?string $mutationTypeName,
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
                    'types' => array_map(
                        $typeRegistry->eagerlyLoadType(...),
                        $this->eagerlyLoadedTypes,
                    ),
                    'typeLoader' => $typeRegistry->eagerlyLoadType(...),
                    'directives' => empty($this->directives) ? null : $this->directives,
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

}