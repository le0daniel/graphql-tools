<?php

declare(strict_types=1);

namespace GraphQlTools;

use Closure;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\SchemaPrinter;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Directories;
use GraphQlTools\Utility\Reflections;
use GraphQlTools\Utility\Types;

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
     * Load All types
     * 
     * @param string $directory
     * @return array
     * @throws \ReflectionException
     */
    public static function createTypeMapFromDirectory(string $directory, bool $includeMetadataTypeExtension = false): array {
        $typeMap = [];

        foreach (Directories::fileIteratorWithRegex($directory, '/\.php$/') as $phpFile) {
            $className = Classes::getDeclaredClassInFile($phpFile->getRealPath());
            if (!$className) {
                continue;
            }

            $parentClassNames = Reflections::getAllParentClasses(new \ReflectionClass($className));
            foreach ($parentClassNames as $parentClassName) {
                if (in_array($parentClassName, self::CLASS_MAP_INSTANCES, true)) {
                    /** @var $className GraphQlUnion|GraphQlType|GraphQlScalar|GraphQlInterface|GraphQlEnum|GraphQlInputType */
                    $typeMap[$className::typeName()] = $className;
                    break;
                }
            }
        }

        return $typeMap;
    }
    
    /**
     * Array containing already initialized types. This ensures the
     * types are only initialized once. The instance of this repository
     * is passed to each type, so they can load the specific instances which are
     * required
     *
     * @var array
     */
    private array $typeInstances = [];

    public function __construct(private array $typeResolutionMap) {}

    /**
     * Create an instance of a given type by either the classname or the type name
     * The default implementation of the Type Repository always expects the types to be
     * a classname and does not work with type names.
     *
     * @param string $className
     * @return mixed
     */
    protected function makeInstanceOfType(string $className): mixed
    {
        return new $className($this);
    }

    /**
     * Make a custom field class
     *
     * @param string $className
     * @return GraphQlField
     */
    public function makeInstanceOfField(string $className): GraphQlField {
        return new $className;
    }

    /**
     * Resolve a given type name to a type
     * 
     * @param string $typeName
     * @return mixed
     */
    private function resolveTypeByName(string $typeName): mixed {
        if (!isset($this->typeInstances[$typeName])) {
            $this->typeInstances[$typeName] = $this->makeInstanceOfType(
                $this->typeResolutionMap[$typeName]
            );
        }

        return $this->typeInstances[$typeName];
    }
    
    /**
     * Returns a specific type by either it's identifier or the type class
     * The default TypeRepository always expects a class name.
     *
     * The functionality can be changed by the Implementor to return a callable
     * and make the schema lazy.
     *
     * @param string $classOrTypeName
     * @return Type|callable
     */
    final public function type(string $classOrTypeName): Type|callable {
        $typeName = Classes::isClassName($classOrTypeName)
            ? $classOrTypeName::typeName()
            : $classOrTypeName;
        
        return fn() => $this->resolveTypeByName($typeName);
    }
    
    final public function nonNullType(string $classOrTypeName): NonNull {
        return new NonNull($this->type($classOrTypeName));
    }

    final public function listOfType(string $className): ListOfType {
        return new ListOfType($this->type($className));
    }

    final public function toSchema(
        string $queryClassOrTypeName,
        ?string $mutationClassOrTypeName = null,
        array $eagerlyLoadTypes = [],
        ?array $directives = null
    ): Schema {
        return new Schema(
            SchemaConfig::create(
                [
                    'query' => Types::enforceTypeLoading($this->type($queryClassOrTypeName)),
                    'mutation' => $mutationClassOrTypeName
                        ? Types::enforceTypeLoading($this->type($mutationClassOrTypeName))
                        : null,
                    'types' => array_map(
                        fn(string $typeName) => Types::enforceTypeLoading($this->type($typeName)),
                        $eagerlyLoadTypes
                    ),
                    'typeLoader' => fn($typeName) => $this->resolveTypeByName($typeName),
                    'directives' => $directives
                ]
            )
        );
    }

    final public static function print(Schema $schema): string {
        return SchemaPrinter::doPrint($schema);
    }

}
