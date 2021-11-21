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
use GraphQlTools\Definition\GraphQlField;

class TypeRepository {
    /**
     * Array containing already initialized types. This ensures the
     * types are only initialized once. The instance of this repository
     * is passed to each type, so they can load the specific instances which are
     * required
     *
     * @var array
     */
    private array $types = [];

    /**
     * Create an instance of a given type by either the classname or the type name
     * The default implementation of the Type Repository always expects the types to be
     * a classname and does not work with type names.
     *
     * @param string $classOrTypeName
     * @return mixed
     */
    protected function makeType(string $classOrTypeName): mixed
    {
        return new $classOrTypeName($this);
    }

    /**
     * Make a custom field class
     *
     * @param string $className
     * @return GraphQlField
     */
    public function makeField(string $className): GraphQlField {
        return new $className;
    }

    /**
     * Used to define the key to cache the types. Types are only initialized once
     * per Schema and cached. It is crucial that types only exist once in a specific
     * Schema
     *
     * @param string $classOrTypeName
     * @return string
     */
    protected function key(string $classOrTypeName): string {
        return $classOrTypeName;
    }

    /**
     * Define a custom type loader.
     *
     * @return Closure|null
     */
    protected function typeLoader(): ?Closure {
        return null;
    }

    /**
     * Enforce creation of the type. This is important when using lazy loading
     * as for example root query and mutation types must be loaded.
     *
     * @param Type|callable $type
     * @return Type
     */
    private static function enforceTypeLoading(Type|callable $type): Type {
        return $type instanceof Type
            ? $type
            : $type();
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
        $className = $this->key($classOrTypeName);

        if (!isset($this->types[$className])) {
            $this->types[$className] = $this->makeType($className);
        }

        return $this->types[$className];
    }

    /**
     * ShortCut for NonNull Type: `Type!`
     *
     * @param string $classOrTypeName
     * @return NonNull
     */
    final public function nonNullType(string $classOrTypeName): NonNull {
        return new NonNull($this->type($classOrTypeName));
    }

    final public function listOfType(string $className, bool $typeIsNullable = true): ListOfType {
        $type = $typeIsNullable
            ? $this->type($className)
            : new NonNull($this->type($className));

        return new ListOfType($type);
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
                    'query' => self::enforceTypeLoading($this->type($queryClassOrTypeName)),
                    'mutation' => $mutationClassOrTypeName
                        ? self::enforceTypeLoading($this->type($mutationClassOrTypeName))
                        : null,
                    'types' => array_map(
                        fn(string $typeName) => self::enforceTypeLoading($this->type($typeName)),
                        $eagerlyLoadTypes
                    ),
                    'typeLoader' => $this->typeLoader(),
                    'directives' => $directives
                ]
            )
        );
    }

    final public static function print(Schema $schema): string {
        return SchemaPrinter::doPrint($schema);
    }

}
