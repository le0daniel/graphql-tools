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
    public function register(DefinesGraphQlType|string $definition, ?string $typeName = null): void
    {
        if (
            $definition instanceof GraphQlDirective
            || (is_string($definition) && Types::isDirective($definition))
        ) {
            $this->registerDirective($definition);
            return;
        }

        $typeName ??= is_string($definition)
            ? Types::inferNameFromClassName($definition)
            : $definition->getName();
        $this->verifyTypeNameIsNotUsed($typeName);
        $this->types[$typeName] = $definition;
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

    public function extend(ExtendGraphQlType|string $extendedType, ?string $extendedTypeName = null): void
    {
        $typeNameOrAlias = match (true) {
            isset($extendedTypeName) => $extendedTypeName,
            $extendedType instanceof ExtendGraphQlType => $extendedType->typeName(),
            is_string($extendedType) => Types::inferNameFromClassName($extendedType),
        };

        if (!$typeNameOrAlias) {
            throw new RuntimeException("Could not infer the name of the type extension.");
        }

        if ($extendedType instanceof ExtendGraphQlType && $typeNameOrAlias !== $extendedType->typeName()) {
            throw new RuntimeException("Extended type name and provided type name hint did not match.");
        }

        $this->typeFieldExtensions[$typeNameOrAlias] ??= [];
        $this->typeFieldExtensions[$typeNameOrAlias][] = $extendedType;
    }

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