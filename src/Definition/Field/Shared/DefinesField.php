<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use DateTimeInterface;

trait DefinesField
{
    protected string|null $description = null;
    protected bool $isBeta = false;
    protected bool $automaticallyRemoveIfPast = false;
    protected string|bool $deprecatedReason = false;
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

    final protected function hideBecauseOfDeprecation(): bool
    {
        return $this->automaticallyRemoveIfPast
            && $this->removalDate
            && $this->removalDate->getTimestamp() < time();
    }

    private function isDeprecated(): bool
    {
        return !!$this->deprecatedReason;
    }

    final protected function computeDeprecationReason(): ?string
    {
        if (!$this->isDeprecated()) {
            return null;
        }

        return $this->removalDate
            ? "{$this->deprecatedReason}. Removal Date: {$this->removalDate->format('Y-m-d')}"
            : $this->deprecatedReason;
    }

    private function computeDeprecatedDescriptionMessage(): string
    {
        return $this->removalDate
            ? '**DEPRECATED**, Removal Date: ' . $this->removalDate->format('Y-m-d') . '.'
            : '**DEPRECATED**, no removal date specified.';
    }

    final protected function computeDescription(): ?string
    {
        $descriptionParts = [];

        if ($this->deprecatedReason) {
            $descriptionParts[] = $this->computeDeprecatedDescriptionMessage();
        }

        if ($this->isBeta) {
            $descriptionParts[] = '**BETA**:';
        }

        if ($this->description) {
            $descriptionParts[] = $this->description;
        }

        return empty($descriptionParts) ? null : implode(' ', $descriptionParts);
    }

}