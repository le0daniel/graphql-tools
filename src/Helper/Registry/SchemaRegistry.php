<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Registry;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\ExtendsGraphQlDefinition;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlDirective;
use GraphQlTools\Utility\Types;
use RuntimeException;

class SchemaRegistry
{
    /**
     * @var array<string,DefinesGraphQlType|string>
     */
    private array $types = [];

    private array $aliases = [];

    /**
     * @var array<string>
     */
    private array $eagerlyLoadedTypes = [];

    /** @var array<GraphQlDirective> */
    private array $directives = [];

    /**
     * @var array<string, array<string, string|ExtendsGraphQlDefinition>>
     */
    private array $extensions = [];

    /**
     * @param DefinesGraphQlType|class-string<DefinesGraphQlType> $definition
     * @throws DefinitionException
     */
    public function register(DefinesGraphQlType|string $definition, ?string $typeName = null): void
    {
        if (Types::isDirective($definition)) {
            $this->registerDirective($definition);
            return;
        }

        $typeName ??= is_string($definition)
            ? Types::inferNameFromClassName($definition)
            : $definition->getName();
        $this->verifyTypeNameIsNotUsed($typeName);

        if ($definition instanceof DefinesGraphQlType && $typeName !== $definition->getName()) {
            throw new RuntimeException("Definition name did not match provided name");
        }

        $alias = is_string($definition) ? $definition : $definition::class;
        $this->types[$typeName] = $definition;
        $this->aliases[$alias] = $typeName;
    }

    /**
     * @throws DefinitionException
     */
    public function registerFromTypeMap(array $types, array $extensions): void {
        $this->registerTypes($types);
        $this->extendMany($extensions);
    }

    /**
     * @param array $types
     * @return void
     * @throws DefinitionException
     */
    public function registerTypes(array $types): void
    {
        foreach ($types as $typeName => $declaration) {
            $this->register($declaration, $typeName);
        }
    }

    public function registerEagerlyLoadedType(string $typeNameOrAlias): void
    {
        $this->eagerlyLoadedTypes[] = $typeNameOrAlias;
    }

    /**
     * Extend a type or interface. If no name is provided, the name is inferred from the
     * classname.
     *
     * @param ExtendsGraphQlDefinition|string $extendedType
     * @param string|null $extendedTypeName
     * @return void
     * @throws DefinitionException
     */
    public function extend(ExtendsGraphQlDefinition|string $extendedType, ?string $extendedTypeName = null): void
    {
        $extendedTypeName ??= $extendedType instanceof ExtendsGraphQlDefinition
            ? $extendedType->typeName()
            : Types::inferExtensionTypeName($extendedType);

        if ($extendedType instanceof ExtendsGraphQlDefinition && $extendedTypeName !== $extendedType->typeName()) {
            throw new RuntimeException("Extended type name and provided type name hint did not match.");
        }

        $this->extensions[$extendedTypeName] ??= [];
        $this->extensions[$extendedTypeName][] = $extendedType;
    }

    /**
     * @param array $extensions
     * @return void
     * @throws DefinitionException
     */
    public function extendMany(array $extensions): void
    {
        foreach ($extensions as $typeName => $definitions) {
            $extendedTypeName = is_int($typeName) ? null : $typeName;
            if (is_array($definitions)) {
                foreach ($definitions as $definition) {
                    $this->extend($definition, $extendedTypeName);
                }
                continue;
            }

            $this->extend($definitions, $extendedTypeName);
        }
    }

    private function registerDirective(GraphQlDirective|string $directive): void
    {
        /** @var GraphQlDirective $instance */
        $instance = is_string($directive) ? new $directive() : $directive;
        $this->directives[$instance->getName()] = $instance;
    }

    private function verifyTypeNameIsNotUsed(string $typeName): void
    {
        if (isset($this->types[$typeName])) {
            throw new RuntimeException("Type with name '{$typeName}' was already registered. You can not register a type twice.");
        }
    }

    protected function resolveExtensionAliases(): array
    {
        $extensionFactories = [];

        foreach ($this->extensions as $typeNameOrAlias => $fieldExtensions) {
            $typeName = $this->aliases[$typeNameOrAlias] ?? $typeNameOrAlias;
            $extensionFactories[$typeName] ??= [];
            array_push($extensionFactories[$typeName], ...$fieldExtensions);
        }

        return $extensionFactories;
    }

    protected function createEagerlyLoadedTypes(): array {
        $eagerlyLoadedTypes = [];
        foreach ($this->eagerlyLoadedTypes as $typeNameOrAlias) {
            $eagerlyLoadedTypes[] = $this->aliases[$typeNameOrAlias] ?? $typeNameOrAlias;
        }
        return array_unique($eagerlyLoadedTypes);
    }

    public function createSchemaConfig(
        ?string      $queryTypeName = null,
        ?string      $mutationTypeName = null,
        bool         $assumeValid = true,
        ?SchemaRules $schemaRules = null,
    ): SchemaConfig
    {
        $schemaRules ??= new AllVisibleSchemaRule();
        $eagerlyLoadedTypes = $this->createEagerlyLoadedTypes();
        $registry = new FactoryTypeRegistry(
            $this->types,
            $this->aliases,
            $this->resolveExtensionAliases(),
            $schemaRules,
        );

        $customDirectives = array_map(
            fn(GraphQlDirective $directive): Directive => $directive->toDefinition($registry, $schemaRules),
            $this->directives
        );

        return SchemaConfig::create(
            [
                'query' => $queryTypeName ? $registry->type($queryTypeName) : null,
                'mutation' => $mutationTypeName ? $registry->type($mutationTypeName) : null,
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