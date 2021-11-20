<?php

declare(strict_types=1);

namespace GraphQlTools;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use GraphQlTools\Utility\Classes;
use GraphQlTools\Utility\Directories;
use ReflectionClass;

class LazyRepository extends TypeRepository {

    private const CLASS_MAP_INSTANCES = [
        GraphQlType::class,
        GraphQlEnum::class,
        GraphQlInputType::class,
        GraphQlInterface::class,
        GraphQlScalar::class,
        GraphQlUnion::class,
    ];

    /** @var Type[]  */
    private array $resolvedTypes = [];

    public function __construct(private array $typeResolutionMap) {

    }

    public static function createTypeMapFromDirectory(string $directory): array {
        $typeMap = [];

        foreach (Directories::fileIteratorWithRegex($directory, '/\.php$/') as $phpFile) {
            $className = Classes::getDeclaredClassInFile($phpFile->getRealPath());
            $reflection = new ReflectionClass($className);

            if (in_array($reflection->getParentClass()?->getName(), self::CLASS_MAP_INSTANCES, true)) {
                $typeMap[$className::typeName()] = $className;
            }
        }

        return $typeMap;
    }

    /**
     * Custom type loader which makes sure to load all types correctly and only resolve them when needed
     *
     * @return Closure|null
     */
    final protected function typeLoader(): ?Closure {
        return function(string $typeName){
            return $this->resolveNameToType($typeName);
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
