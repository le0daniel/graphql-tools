<?php

declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

abstract class Holder implements ArrayAccess, JsonSerializable
{
    final protected function __construct(private readonly array $items) {}

    final public static function verifyListOfInstances(string $className, array $list): void {
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

    final public static function verifyIsInstanceOf(string $className, mixed $object): void {
        if (!$object instanceof $className) {
            $objectClassName = is_object($object) ? get_class($object) : gettype($object);
            throw new RuntimeException("Expected instance of `{$className}`, got `{$objectClassName}`.");
        }
    }

    final public function toArray(): array {
        return $this->items;
    }

    protected function getValue(string $name): mixed {
        return $this->items[$name] ?? null;
    }

    final public function __get(string $name): mixed {
        return $this->getValue($name);
    }

    final public function __isset(string $name): bool{
        return $this->getValue($name) !== null;
    }

    final public function __serialize(): array
    {
        return $this->items;
    }

    final public function __unserialize(array $data)
    {
        // Set readonly Property, allowed here.
        $this->items = $data;
    }

    public function offsetExists($offset): bool {
        return $this->__isset($offset);
    }

    public function offsetGet($offset): mixed {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value): void {
        throw new RuntimeException("Can not set value `{$offset}` of immutable holder.");
    }

    public function offsetUnset($offset): void {
        throw new RuntimeException("Can not unset value `{$offset}` of immutable holder.");
    }

    final public function __set(string $name, mixed $value): void{
        throw new RuntimeException("Can not set value `{$name}` of immutable holder.");
    }

    final public function jsonSerialize(): array {
        $data = [];
        foreach (array_keys($this->items) as $key) {
            $data[$key] = $this->getValue($key);
        }
        return $data;
    }
}
