<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use DateTimeInterface;

trait DefinesField
{
    protected ?string $description = null;
    protected bool $automaticallyRemoveIfPast = false;
    protected string|null $deprecatedReason = null;
    protected DateTimeInterface|null $removalDate = null;

    final public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    final public function deprecated(string $reason, ?DateTimeInterface $removalDate = null, bool $automaticallyRemoveIfPast = false): static
    {
        $this->deprecatedReason = $reason;
        $this->removalDate = $removalDate;
        $this->automaticallyRemoveIfPast = $automaticallyRemoveIfPast;
        return $this;
    }

    final protected function hideFieldBecauseDeprecationDateIsPassed(): bool
    {
        return $this->automaticallyRemoveIfPast
            && isset($this->removalDate)
            && $this->removalDate->getTimestamp() < time();
    }

    final protected function computeDeprecationReason(): ?string
    {
        if (!$this->isDeprecated()) {
            return null;
        }

        return isset($this->removalDate)
            ? "{$this->deprecatedReason}. Removal Date: {$this->removalDate->format('Y-m-d')}"
            : $this->deprecatedReason;
    }

    private function computeDeprecatedDescriptionMessage(): string
    {
        return isset($this->removalDate)
            ? '**DEPRECATED**, Removal Date: ' . $this->removalDate->format('Y-m-d') . '.'
            : '**DEPRECATED**, no removal date specified.';
    }

    final protected function computeDescription(): ?string
    {
        $descriptionParts = [];

        if (isset($this->deprecatedReason)) {
            $descriptionParts[] = $this->computeDeprecatedDescriptionMessage();
        }

        if (isset($this->description)) {
            $descriptionParts[] = $this->description;
        }

        return empty($descriptionParts) ? null : implode(' ', $descriptionParts);
    }

    private function isDeprecated(): bool
    {
        return isset($this->deprecatedReason);
    }

}