<?php

declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

abstract class Holder implements ArrayAccess, JsonSerializable
{
    public const UNDEFINED = '__undefined__';

    final protected function __construct(private readonly array $items) {}

    /**
     * Overwrite this method to serialize custom data properties differently.
     * This is useful for example for DateTime objects, which you might want to serialize
     * to a specific format. The value passed to this method already went through getValue(string $name)
     *
     * You can also use this to omit certain values. If you return NULL, the key will still exist
     * in JSON. If you return Holder::UNDEFINED, the value is omitted from the result completely.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function serializeValue(string $name, mixed $value): mixed {
        return $value;
    }

    /**
     * Overwrite this function to cast certain values into a specific object.
     * This might be useful to cast dates as DateTimeImmutable for example.
     *
     * @param string $name
     * @return mixed
     */
    protected function getValue(string $name): mixed {
        return $this->items[$name] ?? null;
    }

    final public function __serialize(): array
    {
        return $this->items;
    }

    final public function toArray(): array {
        return $this->items;
    }

    final public function __get(string $name): mixed {
        return $this->getValue($name);
    }

    final public function __isset(string $name): bool{
        return $this->getValue($name) !== null;
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
            $serializedValue = $this->serializeValue($propertyName, $this->getValue($propertyName));

            if ($serializedValue !== self::UNDEFINED) {
                $data[$propertyName] = $serializedValue;
            }

        }
        return $data;
    }
}
