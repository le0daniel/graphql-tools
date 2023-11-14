<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use DateTimeInterface;
use GraphQlTools\Utility\Descriptions;

trait HasDeprecation
{
    protected function deprecationReason(): ?string
    {
        return null;
    }

    protected function removalDate(): ?DateTimeInterface
    {
        return null;
    }

    protected function isDeprecated(): bool
    {
        return !!$this->deprecationReason();
    }

    protected function addDeprecationToDescription(string $baseDescription): string
    {
        return $this->isDeprecated()
            ? Descriptions::pretendDeprecationWarning(
                $baseDescription,
                $this->deprecationReason(),
                $this->removalDate()
            )
            : $baseDescription;
    }
}