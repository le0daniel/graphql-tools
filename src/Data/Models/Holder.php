<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;



use ArrayAccess;
use JsonSerializable;

abstract class Holder implements ArrayAccess, JsonSerializable
{
    private const SERIALIZATION_KEY = '__serialize';
    private const SERIALIZATION_ITEMS_FLAG = '__is_list';

    /**
     * Append getters when serializing
     *
     * @var array
     */
    protected array $appendToJsonSerialize = [];

    protected function getValueForSerialization(string $name): mixed {
        return $this->getValue($name);
    }

    final protected function __construct(private array $items) {}

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

    public function offsetExists($offset): bool {
        return $this->__isset($offset);
    }

    public function offsetGet($offset): mixed {
        return $this->getValue($offset);
    }

    public function offsetSet($offset, $value): void {
        throw new \RuntimeException("Can not set value `{$offset}` of immutable holder to: `{$value}`");
    }

    public function offsetUnset($offset): void {
        throw new \RuntimeException("Can not unset value `{$offset}` of immutable holder");
    }

    final public function __set(string $name, mixed $value): void{
        throw new \RuntimeException("Can not set value `{$name}` of immutable holder to: `{$value}`");
    }

    public function jsonSerialize(): array {
        $keys = array_keys($this->items);
        array_push($keys, ...$this->appendToJsonSerialize);

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->getValueForSerialization($key);
        }
        return $data;
    }

    private function serializeKeyValue(mixed $value): mixed {
        $isInstanceOfHolder = $value instanceof Holder;
        $isArrayOfHolder = !$isInstanceOfHolder && is_array($value) && array_is_list($value) && $value[0] instanceof Holder;

        if ($isInstanceOfHolder) {
            return [
                self::SERIALIZATION_KEY => serialize($value),
            ];
        }

        if ($isArrayOfHolder) {
            return [
                self::SERIALIZATION_KEY => array_map(fn(Holder $holder) => serialize($holder), $value),
                self::SERIALIZATION_ITEMS_FLAG => true,
            ];
        }

        return $value;
    }

    private function unserializeValue(mixed $value): mixed {
        if (!is_array($value) || !array_key_exists(self::SERIALIZATION_KEY, $value)) {
            return $value;
        }

        $isList = $value[self::SERIALIZATION_ITEMS_FLAG] ?? false;
        if ($isList) {
            return array_map(fn($data): Holder => unserialize($data), $value[self::SERIALIZATION_KEY]);
        }

        return unserialize($value[self::SERIALIZATION_KEY]);
    }

    final public function __serialize(): array
    {
        $serialized = [];
        foreach ($this->items as $key => $value) {
            $serialized[$key] = $this->serializeKeyValue($value);
        }

        return $serialized;
    }

    final public function __unserialize(array $data)
    {
        $this->items = [];
        foreach ($data as $key => $value) {
            $this->items[$key] = $this->unserializeValue($value);
        }
    }

}
