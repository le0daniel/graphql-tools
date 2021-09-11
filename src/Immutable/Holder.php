<?php

declare(strict_types=1);


namespace GraphQlTools\Immutable;



abstract class Holder implements \ArrayAccess, \JsonSerializable
{
    protected array $appends = [];

    final protected function __construct(private array $items) {}

    final public function toArray(): array {
        return $this->items;
    }

    protected function getValueForSerialization(string $name): mixed {
        return $this->getValue($name);
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

    final public function __set(string $name, mixed $value): void{
        throw new \RuntimeException("Can not set value `{$name}` of immutable holder to: `{$value}`");
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

    public function jsonSerialize(): array {
        $keys = array_keys($this->items);
        array_push($keys, ...$this->appends);

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->getValueForSerialization($key);
        }
        return $data;
    }

}
