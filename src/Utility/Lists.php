<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use Closure;
use RuntimeException;

class Lists
{
    /**
     * @template T
     * @param class-string<T> $className
     * @param array<mixed> $list
     * @return void
     */
    public static function verifyOfType(string $className, array $list): void
    {
        if (!array_is_list($list)) {
            throw new RuntimeException('Expected list got array with keys');
        }

        foreach ($list as $item) {
            if (!$item instanceof $className) {
                $itemClassName = is_object($item) ? get_class($item) : gettype($item);
                throw new RuntimeException("Expected items to be instance of `$className`, got `$itemClassName`.");
            }
        }
    }
}