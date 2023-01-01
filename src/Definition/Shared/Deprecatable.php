<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use DateTimeInterface;

trait Deprecatable
{

    protected ?string $deprecationReason = null;
    protected ?DateTimeInterface $removalDate = null;

    public function deprecated(string $reason, ?DateTimeInterface $removalDate = null): static
    {
        $this->deprecationReason = $reason;
        $this->removalDate = $removalDate;
        return $this;
    }

    protected function isDeprecated(): bool
    {
        return !empty($this->deprecationReason);
    }

    protected function addDeprecationToDescription(string $baseDescription): string
    {
        if (!$this->isDeprecated()) {
            return $baseDescription;
        }

        if ($this->removalDate) {
            return "**Deprecated**: {$this->deprecationReason}. Removal Date: {$this->removalDate->format('Y-m-d H:i:s')}. {$baseDescription}";
        }

        return "**Deprecated**: {$this->deprecationReason}. No removal date specified. {$baseDescription}";
    }

}