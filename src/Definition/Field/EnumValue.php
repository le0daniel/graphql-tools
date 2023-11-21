<?php

namespace GraphQlTools\Definition\Field;

use UnitEnum;

final class EnumValue extends BaseProperties
{
    private mixed $value = null;

    public static function fromDeprecatedConfigArray(string $name, array $config): self {
        $instance = new self($name);
        $instance->value = $config['value'];

        if (isset($config['deprecationReason'])) {
            $instance->deprecationReason = $config['deprecationReason'];
        }

        if (isset($config['description'])) {
            $instance->description = $config['description'];
        }

        return $instance;
    }

    public function value(mixed $value): self {
        $clone = clone $this;
        $clone->value = $value;
        return $clone;
    }

    public static function fromEnum(UnitEnum $enum): self {
        return self::withName($enum->name)->value($enum);
    }

    /**
     * @internal
     * @return array
     */
    public function toDefinition(): array {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'tags' => $this->tags,
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'description' => $this->computeDescription(),
        ];
    }

}
