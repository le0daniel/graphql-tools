<?php

declare(strict_types=1);

namespace GraphQlTools;

use Closure;
use GraphQL\Type\Definition\Type;

class LazyRepository extends TypeRepository {

    /** @var Type[]  */
    private array $resolvedTypes = [];

    public function __construct(private array $typeResolutionMap) {

    }

    public static function createTypeMap(array $classMap): array {
        $typeMap = [];

        foreach ($classMap as $class) {
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
     * A really simple method to check if this is a classname of not. We try to locate
     * a `\`, if it's present in the typename we assume a classname has been given.
     *
     * @param string $potentialClassName
     * @return bool
     */
    private static function isProbablyClassName(string $potentialClassName): bool {
        return str_contains($potentialClassName, '\\');
    }

    /**
     * Returns the correct key to use to store the type.
     *
     * @param string $classOrTypeName
     * @return string
     */
    final protected function key(string $classOrTypeName): string {
        return self::isProbablyClassName($classOrTypeName)
            ? $classOrTypeName::typeName()
            : $classOrTypeName;
    }

    /**
     * Returns a Lazy Type.
     *
     * @param string $classOrTypeName
     * @return Closure
     */
    final protected function make(string $classOrTypeName): Closure {
        return fn() => $this->resolveNameToType($classOrTypeName);
    }
}
