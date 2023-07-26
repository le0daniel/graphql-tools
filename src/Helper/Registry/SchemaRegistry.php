<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use Closure;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\SchemaPrinter;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry as TypeRegistryContract;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Definition\GraphQlDirective;
use GraphQlTools\Utility\Types;
use RuntimeException;

class SchemaRegistry
{
    /**
     * @var array<string,DefinesGraphQlType|string>
     */
    private array $types = [];
    private array $eagerlyLoadedTypes = [];

    /** @var array<GraphQlDirective> */
    private array $directives = [];

    /**
     * @var array<string, array<string|Closure|ExtendGraphQlType>>
     */
    private array $typeFieldExtensions = [];

    public function register(DefinesGraphQlType|string $definition): void
    {
        $typeName = is_string($definition)
            ? Types::inferNameFromClassName($definition)
            : $definition->getName();

        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $definition;
    }

    public function verifyTypeNames(): void
    {
        foreach ($this->types as $name => $definition) {
            $realTypeName = $definition instanceof DefinesGraphQlType
                ? $definition->getName()
                : (new $definition)->getName();

            if ($name !== $realTypeName) {
                throw new DefinitionException("The registered name `{$name}` does not match the name of the type `{$realTypeName}`");
            }
        }
    }

    public function registerType(string $typeName, string|DefinesGraphQlType $typeDeclaration): void
    {
        if ($typeDeclaration instanceof DefinesGraphQlType) {
            $this->register($typeDeclaration);
            return;
        }

        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $typeDeclaration;
    }

    public function registerDirective(GraphQlDirective $directive): void
    {
        $this->directives[] = $directive;
    }

    private function verifyTypeNameIsNotUsed(string $typeName): void
    {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    public function registerTypes(array $types): void
    {
        foreach ($types as $possibleName => $declaration) {
            is_string($possibleName)
                ? $this->registerType($possibleName, $declaration)
                : $this->register($declaration);
        }
    }

    public function registerEagerlyLoadedType(string $typeNameOrAlias): void
    {
        $this->eagerlyLoadedTypes[] = $typeNameOrAlias;
    }

    /**
     * @param string $typeNameOrAlias
     * @param Closure(TypeRegistryContract): array $fieldFactory
     * @return void
     */
    public function extendType(string $typeNameOrAlias, Closure|string|ExtendGraphQlType ...$fieldFactories): void
    {
        if (!isset($this->typeFieldExtensions[$typeNameOrAlias])) {
            $this->typeFieldExtensions[$typeNameOrAlias] = [];
        }
        array_push($this->typeFieldExtensions[$typeNameOrAlias], ...$fieldFactories);
    }

    public function extendTypes(array $extensions): void
    {
        foreach ($extensions as $typeName => $definitions) {
            $this->extendType($typeName, ...$definitions);
        }
    }

    protected function resolveFieldExtensions(array $aliases): array
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

    protected function createAliases(): array
    {
        $aliases = [];
        foreach ($this->types as $typeName => $declaration) {
            if (is_string($declaration)) {
                $aliases[$declaration] = $typeName;
            }
        }
        return $aliases;
    }

    public function createSchemaConfig(
        ?string      $queryTypeName = null,
        ?string      $mutationTypeName = null,
        bool         $assumeValid = true,
        ?SchemaRules $schemaRules = null,
    ): SchemaConfig
    {
        $aliases = $this->createAliases();
        $eagerlyLoadedTypes = $this->eagerlyLoadedTypes;
        $registry = new FactoryTypeRegistry(
            $this->types,
            $aliases,
            $this->resolveFieldExtensions($aliases),
            $schemaRules,
        );

        $queryType = $queryTypeName
            ? Schema::resolveType($registry->type($queryTypeName))
            : null;

        $mutationType = $mutationTypeName
            ? Schema::resolveType($registry->type($mutationTypeName))
            : null;

        return SchemaConfig::create(
            [
                'query' => $queryType,
                'mutation' => $mutationType,
                'types' => static function () use ($eagerlyLoadedTypes, $registry) {
                    $types = [];
                    foreach ($eagerlyLoadedTypes as $name) {
                        /** @var ObjectType $type */
                        $type = Schema::resolveType($registry->type($name));

                        // For eagerly loaded types we verify if the fields are defined or not
                        if (!empty($type->getFields())) {
                            $types[] = $type;
                        }
                    }
                    return $types;
                },
                'typeLoader' => static function (string $typeNameOrClassName) use ($registry) {
                    try {
                        return Schema::resolveType($registry->type($typeNameOrClassName));
                    } catch (DefinitionException) {
                        return null;
                    }
                },
                'assumeValid' => $assumeValid,
                'directives' => array_map(fn(GraphQlDirective $directive): Directive => $directive->toDefinition(), $this->directives) + GraphQL::getStandardDirectives(),
            ]
        );
    }

    public function printPartial(?SchemaRules $schemaRules = null): string
    {
        $aliases = $this->createAliases();
        $registry = new PartialPrintRegistry(
            $this->types,
            $aliases,
            $extensions = $this->resolveFieldExtensions($aliases),
            $schemaRules,
        );

        // This step is required to get type hints for interface types.
        $typeNames = array_unique([...$registry->getTypeNames(), ...array_keys($extensions)]);

        $schema = new Schema(SchemaConfig::create([
            'types' => array_filter(
                array_map(function(string $name) use ($registry) {
                    /** @var Type $type */
                    $type = Schema::resolveType($registry->type($name));
                    $hasFields = $type instanceof ObjectType || $type instanceof InterfaceType || $type instanceof InputObjectType;
                    $isEmpty = $hasFields && empty($type->getFields());
                    return $isEmpty ? null : $type;
                }, $typeNames)
            ),
        ]));

        return SchemaPrinter::doPrint($schema);
    }

    /**
     * @param string|null $queryTypeName
     * @param string|null $mutationTypeName
     * @param bool $assumeValid
     * @return Schema
     */
    public function createSchema(
        ?string      $queryTypeName = null,
        ?string      $mutationTypeName = null,
        bool         $assumeValid = true,
        ?SchemaRules $schemaRules = null,
    ): Schema
    {
        return new Schema(
            $this->createSchemaConfig(
                $queryTypeName,
                $mutationTypeName,
                $assumeValid,
                $schemaRules
            )
        );
    }

}