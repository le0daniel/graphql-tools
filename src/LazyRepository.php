<?php

declare(strict_types=1);

namespace GraphQlTools;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Utility\Classes;

class LazyRepository extends TypeRepository {

    /** @var Type[]  */
    private array $resolvedTypes = [];

    public function __construct(private array $typeResolutionMap) {

    }

    public static function createTypeMap(array $classes): array {
        $typeMap = [];

        foreach ($classes as $class) {
            $typeMap[($class . '::typeName')()] = $class;
        }

        return $typeMap;
    }

    final protected function typeLoader(): ?Closure {
        return function(string $name){
            return $this->resolveNameToType($name);
        };
    }

    /**
     * Creates an instance of a type given its class name. The classname is resolved
     * in resolveNameToType and the type is cached afterwards.
     *
     * @param string $className
     * @return Type
     */
    protected function makeInstanceOfType(string $className): Type {
        return new $className($this);
    }

    /**
     *
     * @param string $typeName
     * @return Type
     */
    final protected function resolveNameToType(string $typeName): Type {
        if (!isset($this->resolvedTypes[$typeName])) {
            $className = $this->typeResolutionMap[$typeName];
            $this->resolvedTypes[$typeName] = $this->makeInstanceOfType($className);
        }

        return $this->resolvedTypes[$typeName];
    }

    /**
     * Returns the correct key to use to store the type under.
     *
     * @param string $classOrTypeName
     * @return string
     */
    final protected function key(string $classOrTypeName): string {
        return Classes::mightBeClassName($classOrTypeName)
            ? $classOrTypeName::typeName()
            : $classOrTypeName;
    }

    /**
     * Returns a Lazy Type.
     *
     * @param string $typeName
     * @return Closure
     */
    final protected function makeType(string $typeName): Closure {
        return fn() => $this->resolveNameToType($typeName);
    }
}
