<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Deprecations;

use DateTimeInterface;
use JsonSerializable;

final class DeprecatedField implements JsonSerializable
{

    public function __construct(
        public readonly string             $fieldName,
        public readonly string             $parentTypeName,
        public readonly string             $reason,
        public readonly ?DateTimeInterface $removalDate = null,
    )
    {
    }

    public function toString(): string
    {
        if (empty($this->removalDate)) {
            return "Deprecated field {$this->parentTypeName}.{$this->fieldName}: {$this->reason}.";
        }
        return "Deprecated field {$this->parentTypeName}.{$this->fieldName}: {$this->reason}. Removal Date: {$this->removalDate?->format('Y-m-d H:i:s')}.";
    }

    public function jsonSerialize(): mixed
    {
        return [
            'fieldName' => $this->fieldName,
            'parentTypeName' => $this->parentTypeName,
            'reason' => $this->reason,
            'removalDate' => $this->removalDate?->format('Y-m-d H:i:s'),
            'message' => $this->toString(),
        ];
    }
}