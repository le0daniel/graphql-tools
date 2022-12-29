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
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\TypeMap;
use ReflectionException;
use RuntimeException;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;

class TypeRegistry implements TypeRegistryContract
{
    /**
     * Represents the opposite of the $typeResolutionMap.
     * This is used to determine the type name, given a classname.
     *
     * @var array<class-string, string>
     */
    private array $classNameToTypeNameMap;

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
     * @var array<string, array<Closure(TypeRegistry):array<Field>>
     */
    private array $typeExtensions = [];

    private array $eagerlyLoadTypes = [];

    /**
     * @param array<string, class-string> $typeResolutionMap
     */
    public function __construct(private array $typeResolutionMap)
    {
        $this->classNameToTypeNameMap = array_flip($typeResolutionMap);
    }

    /**
     * @param string $directory
     * @return array<string, class-string>
     * @throws ReflectionException
     * @deprecated Use TypeMap::createTypeMapFromDirectory instead
     */
    final public static function createTypeMapFromDirectory(string $directory): array
    {
        return TypeMap::createTypeMapFromDirectory($directory);
    }

    public function getTypeMap(): array {
        return [];
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
     * @param string|class-string<Type> $classOrTypeName
     * @return Closure(): Type
     */
    public function type(string $classOrTypeName): Closure
    {
        return fn() => $this->resolveTypeByName($this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName);
    }

    /**
     * @param string|class-string<Type> $classOrTypeName
     * @return Type
     */
    public function eagerlyLoadType(string $classOrTypeName): Type
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
    public function toSchema(
        string  $queryClassOrTypeName,
        ?string $mutationClassOrTypeName = null,
        array   $eagerlyLoadTypes = [],
        ?array  $directives = null,
        bool    $assumeValid = true,
    ): Schema
    {
        return new Schema(
            SchemaConfig::create(
                [
                    'query' => $this->eagerlyLoadType($queryClassOrTypeName),
                    'mutation' => $mutationClassOrTypeName
                        ? $this->eagerlyLoadType($mutationClassOrTypeName)
                        : null,
                    'types' => array_map(
                        fn(string $typeName) => $this->eagerlyLoadType($typeName),
                        array_merge($eagerlyLoadTypes, $this->eagerlyLoadTypes)
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

    /**
     * Resolves a type name to an instance of a type.
     *
     * @param string $typeName
     * @return Type
     */
    private function resolveTypeByName(string $typeName): Type
    {
        if (!isset($this->typeInstances[$typeName])) {
            $this->typeInstances[$typeName] = $this->createTypeByTypeName($typeName);
        }

        return $this->typeInstances[$typeName];
    }

    private function verifyCanStillMutateSchema(): void
    {
        if (count($this->typeInstances) !== 0) {
            throw new RuntimeException("Tried to extend the schema after a schema was built.");
        }
    }

    private function createTypeByTypeName(string $typeName): Type
    {
        /** @var class-string<Type> $className */
        $className = $this->typeResolutionMap[$typeName] ?? null;

        if (!$className) {
            throw new RuntimeException("Could not resolve type with name `{$typeName}`. Is it in the type-map?");
        }

        // Append Extensions to the type
        if (isset($this->typeExtensions[$typeName])) {

            return new $className($this, $this->typeExtensions[$typeName]);
        }

        return new $className($this);
    }

    public function registerEagerlyLoadedType(string $classOrTypeName): void
    {
        $this->verifyCanStillMutateSchema();
        $this->eagerlyLoadTypes[] = $classOrTypeName;
    }

    public function registerTypes(array $typeMap): void
    {
        $this->verifyCanStillMutateSchema();
        foreach ($typeMap as $typeName => $className) {
            $this->classNameToTypeNameMap[$className] = $typeName;
            $this->typeResolutionMap[$typeName] = $className;
        }
    }

    /**
     * @param string $classOrTypeName
     * @param Closure(TypeRegistry):array<Field> $registerFields
     * @return void
     */
    public function extendTypeFields(string $classOrTypeName, Closure $registerFields): void
    {
        $this->verifyCanStillMutateSchema();
        $typeName = $this->classNameToTypeNameMap[$classOrTypeName] ?? $classOrTypeName;
        if (!isset($this->typeExtensions[$typeName])) {
            $this->typeExtensions[$typeName] = [];
        }
        $this->typeExtensions[$typeName][] = $registerFields;
    }
}
