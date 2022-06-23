<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use RuntimeException;

class Instances
{
    /**
     * @param class-string $className
     * @param mixed $object
     * @return void
     */
    public static function verifyOfType(string $className, mixed $object): void
    {
        if (!$object instanceof $className) {
            $objectClassName = is_object($object) ? get_class($object) : gettype($object);
            throw new RuntimeException("Expected instance of `{$className}`, got `{$objectClassName}`.");
        }
    }
}