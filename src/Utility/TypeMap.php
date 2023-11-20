<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\ExtendType;
use GraphQlTools\Definition\DefinitionException;
use ReflectionClass;
use ReflectionException;

class TypeMap
{
    /**
     * @api
     * @param string $directory
     * @return array{0:array<string, string>, 1:array<string,array<string>>}
     * @throws DefinitionException
     * @throws ReflectionException
     */
    final public static function createTypeMapFromDirectory(string $directory): array
    {
        $typeMap = [];
        $extendedTypes = [];

        foreach (Directories::fileIteratorWithRegex($directory, '/\.php$/') as $phpFile) {
            $className = Classes::getDeclaredClassInFile($phpFile->getRealPath());
            if (!$className) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if ($reflection->implementsInterface(ExtendType::class)) {
                self::verifyHasConstructorWithoutArguments($reflection);

                /** @var ExtendType $instance */
                $instance = (new $className);
                $extendedTypes[$instance->typeName()][] = $className;
                continue;
            }

            if (!$reflection->implementsInterface(DefinesGraphQlType::class)) {
                continue;
            }

            self::verifyHasConstructorWithoutArguments($reflection);

            /** @var DefinesGraphQlType $instance */
            $instance = (new $className);
            $typeName = $instance->getName();
            $typeMap[$typeName] = $className;
        }

        return [$typeMap, $extendedTypes];
    }

    /**
     * @throws DefinitionException
     */
    private static function verifyHasConstructorWithoutArguments(ReflectionClass $reflection): void {
        if ($reflection->getConstructor() && $reflection->getConstructor()->getNumberOfParameters() !== 0) {
            throw new DefinitionException("A type should not have a constructor or only have a constructor without any parameters. This ensures that there are no side effects when a resolver is called multiple times.");
        }
    }

    /**
     * @api
     * @throws ReflectionException|DefinitionException
     */
    final public static function createTypeMapFromDirectories(string ... $directories): array {
        $combinedTypeMap = [];
        $combinedExtendedTypes = [];

        foreach ($directories as $directory) {
            [$typeMap, $extendedTypes] = self::createTypeMapFromDirectory($directory);
            $combinedTypeMap = Arrays::mergeKeyValues($combinedTypeMap, $typeMap);
            $combinedExtendedTypes = Arrays::mergeKeyValues($combinedExtendedTypes, $extendedTypes);
        }
        return [$combinedTypeMap, $combinedExtendedTypes];
    }

}