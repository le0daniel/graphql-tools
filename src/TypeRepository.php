<?php

declare(strict_types=1);

namespace GraphQlTools;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\SchemaPrinter;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use GraphQlTools\CustomIntrospection\TypeMetadataType;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Directories;
use GraphQlTools\Utility\Reflections;
use ReflectionClass;
use RuntimeException;

class TypeRepository {

    private const CLASS_MAP_INSTANCES = [
        GraphQlType::class,
        GraphQlEnum::class,
        GraphQlInputType::class,
        GraphQlInterface::class,
        GraphQlScalar::class,
        GraphQlUnion::class,
    ];

    /**
     * Represents the opposite of the $typeResolutionMap.
     * This is used to determine the type name, given a classname.
     *
     * @var array
     */
    private array $classNameToTypeNameMap;

    /**
     * Array containing already initialized types. This ensures the
     * types are only initialized once. The instance of this repository
     * is passed to each type, so they can load the specific instances which are
     * required
     *
     * @var array
     */
    private array $typeInstances = [];

    public function __construct(private array $typeResolutionMap) {
        $this->classNameToTypeNameMap = array_flip($typeResolutionMap);
    }

    public static function createTypeMapFromDirectory(string $directory, bool $includeMetadataTypeExtension = false): array {
        $typeMap = [];

        foreach (Directories::fileIteratorWithRegex($directory, '/\.php$/') as $phpFile) {
            $className = Classes::getDeclaredClassInFile($phpFile->getRealPath());
            if (!$className) {
                continue;
            }

            $parentClassNames = Reflections::getAllParentClasses(new ReflectionClass($className));
            foreach ($parentClassNames as $parentClassName) {
                if (in_array($parentClassName, self::CLASS_MAP_INSTANCES, true)) {
                    /** @var $className GraphQlUnion|GraphQlType|GraphQlScalar|GraphQlInterface|GraphQlEnum|GraphQlInputType */
                    $typeMap[$className::typeName()] = $className;
                    break;
                }
            }
        }

        return $includeMetadataTypeExtension
            ? Arrays::mergeKeyValues($typeMap, TypeMetadataType::typeMap())
            : $typeMap;
    }

    /**
     * This method can be used to completely hide fields depending on a configuration
     *
     * You might want to only expose types which are public and not in beta for example.
     *
     * @param bool $isBeta
     * @param mixed $fieldMetadata
     * @return bool
     */
    public function shouldHideField(bool $isBeta, mixed $fieldMetadata): bool {
        return false;
    }

    private function resolveType(string $typeName): Type {
        if (!isset($this->typeInstances[$typeName])) {
            $className = $this->typeResolutionMap[$typeName] ?? null;

            if (!$className) {
                throw new RuntimeException("Could not resolve type `{$typeName}`. Is it in the type-map?");
            }

            $this->typeInstances[$typeName] = new $className($this);
        }

        return $this->typeInstances[$typeName];
    }

    private function eagerlyResolveType(string $classOrTypeName): Type {
        return $this->resolveType($this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName);
    }

    final public function type(string $classOrTypeName): callable {
        return fn() => $this->resolveType($this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName);
    }

    final public function toSchema(
        string $queryClassOrTypeName,
        ?string $mutationClassOrTypeName = null,
        array $eagerlyLoadTypes = [],
        ?array $directives = null,
        bool $assumeValid = true,
    ): Schema {
        /** @var GraphQlType $rootQueryType */
        $rootQueryType = $this->eagerlyResolveType($queryClassOrTypeName);

        // Append Metadata Query to the root query.
        if (array_key_exists(TypeMetadataType::TYPE_NAME, $this->typeResolutionMap)) {
            $rootQueryType->appendField(TypeMetadataType::rootQueryField($this));
        }

        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $rootQueryType,
                    'mutation' => $mutationClassOrTypeName
                        ? $this->eagerlyResolveType($mutationClassOrTypeName)
                        : null,
                    'types' => array_map(
                        fn(string $typeName) => $this->eagerlyResolveType($typeName),
                        $eagerlyLoadTypes
                    ),
                    'typeLoader' => $this->resolveType(...),
                    'directives' => $directives,
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

    final public static function print(Schema $schema): string {
        return SchemaPrinter::doPrint($schema);
    }

}
