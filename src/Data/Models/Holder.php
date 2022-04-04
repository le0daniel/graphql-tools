<?php

declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

abstract class Holder implements ArrayAccess, JsonSerializable
{
    final protected function __construct(private readonly array $items) {}

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

    protected function serializeValue(string $name, mixed $value): mixed {
        return $value;
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
        foreach (array_keys($this->items) as $propertyName) {
            $data[$propertyName] = $this->serializeValue($propertyName, $this->getValue($propertyName));
        }
        return $data;
    }
}
