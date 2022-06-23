<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\SchemaPrinter;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Directories;
use GraphQlTools\Utility\Reflections;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class TypeRegistry
{
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
     * @var array<class-string, string>
     */
    private readonly array $classNameToTypeNameMap;

    /**
     * Array containing already initialized types. This ensures the
     * types are only initialized once. The instance of this repository
     * is passed to each type, so they can load the specific instances which are
     * required
     *
     * @var array<string, Type>
     */
    private array $typeInstances = [];

    /**
     * @param array<string, class-string> $typeResolutionMap
     */
    public function __construct(
        private readonly array $typeResolutionMap,
        public readonly bool   $lazyResolveFields = false
    )
    {
        $this->classNameToTypeNameMap = array_flip($typeResolutionMap);
    }

    /**
     * This is expensive and should only be used during development. We suggest
     * that you build the TypeMap during the build process of your application
     * and cache it for production.
     *
     * @param string $directory
     * @return array<string, class-string>
     * @throws ReflectionException
     */
    final public static function createTypeMapFromDirectory(string $directory): array
    {
        $typeMap = [];

        foreach (Directories::fileIteratorWithRegex($directory, '/\.php$/') as $phpFile) {
            $className = Classes::getDeclaredClassInFile($phpFile->getRealPath());
            if (!$className) {
                continue;
            }

            $parentClassNames = Reflections::getAllParentClasses(new ReflectionClass($className));
            foreach ($parentClassNames as $parentClassName) {
                if (in_array($parentClassName, self::CLASS_MAP_INSTANCES, true)) {
                    /** @var class-string<GraphQlUnion|GraphQlType|GraphQlScalar|GraphQlInterface|GraphQlEnum|GraphQlInputType> $className */
                    $typeMap[$className::typeName()] = $className;
                    break;
                }
            }
        }

        return $typeMap;
    }

    /**
     * This method can be used to completely hide fields depending on a configuration
     *
     * You might want to only expose types which are public and not in beta for example.
     * You can use $field->ofSchemaVariant(Enum::MySchemaVariantPublic) to set an
     * arbitrary value as schema variant. Then you can use the registry to dynamically
     * hide the field. Additionally, you can use field Metadata to further add context.
     *
     * @param Field $field
     * @return bool
     */
    public function shouldHideField(Field $field): bool
    {
        return false;
    }

    /**
     * This method can be used to completely hide fields depending on a configuration
     *
     * You might want to only expose types which are public and not in beta for example.
     * You can use $argumentOrInputField->ofSchemaVariant(Enum::MySchemaVariantPublic) to set an
     * arbitrary value as schema variant. Then you can use the registry to dynamically
     * hide the field. Additionally, you can use field Metadata to further add context.
     *
     * @param InputField $inputField
     * @return bool
     */
    public function shouldHideInputField(InputField $inputField): bool
    {
        return false;
    }

    /**
     * @param string $classOrTypeName
     * @return Closure(): Type
     */
    final public function type(string $classOrTypeName): Closure
    {
        return fn() => $this->resolveTypeByName($this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName);
    }

    /**
     * @param string $classOrTypeName
     * @return Type
     */
    final public function eagerlyLoadType(string $classOrTypeName): Type
    {
        $typeName = $this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName;
        return $this->resolveTypeByName($typeName);
    }

    /**
     * @param string $queryClassOrTypeName
     * @param string|null $mutationClassOrTypeName
     * @param array<string> $eagerlyLoadTypes
     * @param array<Directive>|null $directives
     * @param bool $assumeValid
     * @return Schema
     */
    final public function toSchema(
        string  $queryClassOrTypeName,
        ?string $mutationClassOrTypeName = null,
        array   $eagerlyLoadTypes = [],
        ?array  $directives = null,
        bool    $assumeValid = true,
    ): Schema
    {
        /** @var GraphQlType $rootQueryType */
        $rootQueryType = $this->eagerlyLoadType($queryClassOrTypeName);

        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $rootQueryType,
                    'mutation' => $mutationClassOrTypeName
                        ? $this->eagerlyLoadType($mutationClassOrTypeName)
                        : null,
                    'types' => array_map(
                        fn(string $typeName) => $this->eagerlyLoadType($typeName),
                        $eagerlyLoadTypes
                    ),
                    'typeLoader' => $this->resolveTypeByName(...),
                    'directives' => $directives,
                    'assumeValid' => $assumeValid,
                ]
            )
        );
    }

    final public static function print(Schema $schema): string
    {
        return SchemaPrinter::doPrint($schema);
    }

    private function resolveTypeByName(string $typeName): Type
    {
        if (!isset($this->typeInstances[$typeName])) {
            /** @var class-string<Type> $className */
            $className = $this->typeResolutionMap[$typeName] ?? null;

            if (!$className) {
                throw new RuntimeException("Could not resolve type with name `{$typeName}`. Is it in the type-map?");
            }

            $this->typeInstances[$typeName] = new $className($this);
        }

        return $this->typeInstances[$typeName];
    }


}
