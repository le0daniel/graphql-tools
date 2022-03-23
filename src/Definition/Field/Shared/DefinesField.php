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

    final public function isDeprecated(string $reason, ?DateTimeInterface $removalDate = null, bool $automaticallyRemoveIfPast = false): static
    {
        $this->deprecatedReason = $reason;
        $this->removalDate = $removalDate;
        $this->automaticallyRemoveIfPast = $automaticallyRemoveIfPast;
        return $this;
    }

    final protected function hideBecauseOfDeprecation() {
        return $this->automaticallyRemoveIfPast
            && $this->removalDate
            && $this->removalDate->getTimestamp() < time();
    }

    final public function isBeta(): static
    {
        $this->isBeta = true;
        return $this;
    }

    final protected function computeDeprecationReason(): ?string {
        if (!$this->deprecatedReason) {
            return null;
        }

        return $this->removalDate
            ? "{$this->deprecatedReason}. Removal Date: {$this->removalDate->format('Y-m-d')}"
            : $this->deprecatedReason;
    }

    final protected function computeDescription(): ?string
    {
        $descriptionParts = [];

        if ($this->deprecatedReason) {
            $descriptionParts[] = '**DEPRECATED**, Removal Date: ' . $this->removalDate->format('Y-m-d') . '.';
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