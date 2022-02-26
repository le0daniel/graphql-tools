<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesField
{

    protected string|null $description = null;
    protected bool $isBeta = false;
    protected string|bool $deprecatedReason = false;
    protected \DateTimeInterface|null $removalDate = null;

    final public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    final public function isDeprecated(string $reason, \DateTimeInterface $removalDate): static
    {
        $this->deprecatedReason = $reason;
        $this->removalDate = $removalDate;
        return $this;
    }

    final public function isBeta(): static
    {
        $this->isBeta = true;
        return $this;
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