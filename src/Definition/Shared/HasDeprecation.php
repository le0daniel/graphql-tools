<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use DateTimeInterface;

trait HasDeprecation
{
    protected function deprecationReason(): ?string {
        return null;
    }

    protected function removalDate(): ?DateTimeInterface {
        return null;
    }

    protected function addDeprecationToDescription(string $baseDescription): string
    {
        if (empty($this->deprecationReason())) {
            return $baseDescription;
        }

        if ($this->removalDate()) {
            return "**Deprecated**: {$this->deprecationReason()}. Removal Date: {$this->removalDate()->format('Y-m-d H:i:s')}. {$baseDescription}";
        }

        return "**Deprecated**: {$this->deprecationReason()}. No removal date specified. {$baseDescription}";
    }
}