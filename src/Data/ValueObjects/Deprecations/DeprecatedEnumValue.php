<?php

namespace GraphQlTools\Data\ValueObjects\Deprecations;

use JsonSerializable;

final class DeprecatedEnumValue implements JsonSerializable
{
    public function __construct(
        public readonly string $enumTypeName,
        public readonly string $valueName,
        public readonly string $reason,
        public readonly ?\DateTimeInterface $removalDate = null,
    )
    {
    }

    public function toString(): string
    {
        $message = "Deprecated Enum value `{$this->enumTypeName}`.`{$this->valueName}` used: {$this->reason}.";
        return $this->removalDate
            ? "{$message} Removal Date: {$this->removalDate->format('Y-m-d H:i:s')}."
            : $message;
    }

    public function jsonSerialize(): array
    {
        return [
            'enumTypeName' => $this->enumTypeName,
            'valueName' => $this->valueName,
            'reason' => $this->reason,
            'removalDate' => $this->removalDate?->format('Y-m-d H:i:s'),
            'message' => $this->toString(),
        ];
    }
}
