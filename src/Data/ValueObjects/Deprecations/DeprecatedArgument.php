<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Deprecations;

final class DeprecatedArgument implements \JsonSerializable
{
    public function __construct(
        public readonly string              $fieldName,
        public readonly string              $parentTypeName,
        public readonly string              $argumentName,
        public readonly string              $reason,
        public readonly ?\DateTimeInterface $removalDate = null,
    )
    {
    }

    public function toString(): string
    {
        $message = "Deprecated argument `{$this->argumentName}` used on `{$this->parentTypeName}`.`{$this->fieldName}`: {$this->reason}.";
        return $this->removalDate
            ? "{$message} Removal Date {$this->removalDate->format('Y-m-d H:i:s')}."
            : $message;
    }

    public function jsonSerialize(): array
    {
        return [
            'fieldName' => $this->fieldName,
            'parentTypeName' => $this->parentTypeName,
            'argumentName' => $this->argumentName,
            'reason' => $this->reason,
            'removalDate' => $this->removalDate,
            'message' => $this->toString(),
        ];
    }
}