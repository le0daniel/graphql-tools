<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQlTools\Definition\DefinitionException;
use RuntimeException;

class Typing
{
    /**
     * @param class-string $className
     * @param mixed $object
     * @return void
     */
    public static function verifyOfType(string $className, mixed $object): void
    {
        if (!$object instanceof $className) {
            throw DefinitionException::from($object, $className);
        }
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param array<mixed> $list
     * @return void
     */
    public static function verifyListOfType(string $className, array $list): void
    {
        if (!array_is_list($list)) {
            throw new RuntimeException('Expected list got array with keys');
        }

        foreach ($list as $instance) {
            if (!$instance instanceof $className) {
                throw DefinitionException::from($instance, $className);
            }
        }
    }
}