<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

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
            if ($reflection->isAbstract()) {
                continue;
            }

            $parentClassNames = Reflections::getAllParentClasses($reflection);
            foreach ($parentClassNames as $parentClassName) {
                if (in_array($parentClassName, self::CLASS_MAP_INSTANCES, true)) {
                    $typeName = (new $className)->getName();
                    $typeMap[$typeName] = $className;
                    break;
                }
            }
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