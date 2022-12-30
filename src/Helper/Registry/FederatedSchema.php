<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
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

    public function extendType(string $typeOrClassName, Closure $fieldFactory): void
    {
        if (!isset($this->typeFieldExtensions[$typeOrClassName])) {
            $this->typeFieldExtensions[$typeOrClassName] = [];
        }
        $this->typeFieldExtensions[$typeOrClassName][] = $fieldFactory;
    }

    protected function createInstanceOfTypeRegistry(array $typeResolutionMap): TypeRegistryContract
    {
        return new TypeRegistry(
            $typeResolutionMap,
            array_flip($typeResolutionMap)
        );
    }

    public function createSchema(
        string $queryTypeName,
        ?string $mutationTypeName,
        bool $assumeValid = true,
    ): Schema {
        $typeRegistry = $this->createInstanceOfTypeRegistry($this->typeResolutionMap);

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