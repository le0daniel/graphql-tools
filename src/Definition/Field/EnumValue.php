<?php

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\Field\Shared\DefinesBaseProperties;
use UnitEnum;

final class EnumValue
{
    use DefinesBaseProperties;

    private mixed $value = null;

    final private function __construct(public readonly string $name)
    {
    }

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

    public function value(mixed $value): self {
        $this->value = $value;
        return $this;
    }

    public static function fromEnum(UnitEnum $enum): self {
        return self::withName($enum->name)->value($enum);
    }

    public static function withName(string $name): self {
        return new self($name);
    }

}
