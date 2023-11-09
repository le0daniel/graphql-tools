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

    /**
     * @param DefinesGraphQlType|class-string<DefinesGraphQlType> $definition
     * @throws DefinitionException
     */
    public function register(DefinesGraphQlType|string $definition): void
    {
        if ($definition instanceof GraphQlDirective || (is_string($definition) && str_ends_with($definition, 'Directive'))) {
            $this->registerDirective($definition);
            return;
        }

        $typeName = is_string($definition)
            ? Types::inferNameFromClassName($definition)
            : $definition->getName();
        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $definition;
    }

    private function registerDirective(GraphQlDirective|string $directive): void
    {
        /** @var GraphQlDirective $instance */
        $instance = is_string($directive) ? new $directive() : $directive;
        $this->directives[$instance->getName()] = $directive;
    }

    private function verifyTypeNameIsNotUsed(string $typeName): void
    {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    /**
     * @param array $types
     * @return void
     * @throws DefinitionException
     */
    public function registerTypes(array $types): void
    {
        foreach ($types as $declaration) {
            $this->register($declaration);
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
        $this->typeFieldExtensions[$typeNameOrAlias] ??= [];
        array_push($this->typeFieldExtensions[$typeNameOrAlias], ...$fieldFactories);
    }

    public function extendTypes(array $extensions): void
    {
        foreach ($extensions as $typeName => $definitions) {
            $fieldFactories = is_array($definitions) ? $definitions : [$definitions];
            $this->extendType($typeName, ...$fieldFactories);
        }
    }

    protected function resolveFieldExtensions(array $aliases): array
    {
        $extensionFactories = [];

        foreach ($this->typeFieldExtensions as $typeNameOrAlias => $fieldExtensions) {
            $typeName = $aliases[$typeNameOrAlias] ?? $typeNameOrAlias;
            $extensionFactories[$typeName] ??= [];
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
        $schemaRules ??= new AllVisibleSchemaRule();
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

        $customDirectives = array_map(
            fn(GraphQlDirective $directive): Directive => $directive->toDefinition($registry, $schemaRules),
            $this->directives
        );

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
                    } catch (DefinitionException $exception) {
                        // The GraphQL framework supports defaults for Query, Mutation or Subscription types when printing the schema
                        // In that case, we have to return null to not get an exception
                        if (in_array($typeNameOrClassName, ['Query', 'Mutation', 'Subscription'])) {
                            return null;
                        }
                        throw $exception;
                    }
                },
                'assumeValid' => $assumeValid,
                'directives' => $customDirectives + GraphQL::getStandardDirectives(),
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
            $schemaRules ?? new AllVisibleSchemaRule(),
        );

        // This step is required to get type hints for interface types.
        $typeNames = array_unique([...$registry->getTypeNames(), ...array_keys($extensions)]);

        $schema = new Schema(SchemaConfig::create([
            'types' => array_filter(
                array_map(function (string $name) use ($registry) {
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