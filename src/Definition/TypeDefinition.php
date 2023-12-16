<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use DateTimeInterface;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Utility\Descriptions;

abstract class TypeDefinition implements DefinesGraphQlType
{
    final public function __construct()
    {
    }

    protected function description(): string
    {
        return '';
    }

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

    protected function computeDescription(): string
    {
        return $this->isDeprecated()
            ? Descriptions::pretendDeprecationWarning(
                $this->description(),
                $this->deprecationReason(),
                $this->removalDate()
            )
            : $this->description();
    }

}