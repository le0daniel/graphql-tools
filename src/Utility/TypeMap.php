<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\GraphQlEnum;
use GraphQlTools\Definition\GraphQlInputType;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlScalar;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\GraphQlUnion;
use ReflectionClass;
use ReflectionException;

class TypeMap
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

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if (!$reflection->implementsInterface(DefinesGraphQlType::class)) {
                continue;
            }

            if ($reflection->getConstructor() && $reflection->getConstructor()->getNumberOfParameters() !== 0) {
                throw new DefinitionException("A type should not have a constructor or only have a constructor without any parameters. This ensures that there are no side effects when a resolver is called multiple times.");
            }

            /** @var DefinesGraphQlType $instance */
            $instance = (new $className);
            $typeName = $instance->getName();
            $typeMap[$typeName] = $className;
        }

        return $typeMap;
    }

    /**
     * @throws ReflectionException
     */
    final public static function createTypeMapFromDirectories(string ... $directories): array {
        $typeMap = [];
        foreach ($directories as $directory) {
            $typeMap = Arrays::mergeKeyValues($typeMap, self::createTypeMapFromDirectory($directory));
        }
        return $typeMap;
    }

}