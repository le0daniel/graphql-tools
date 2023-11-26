<?php declare(strict_types=1);

namespace GraphQlTools\Definition;

use DateTimeInterface;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Utility\Descriptions;

abstract class TypeDefinition implements DefinesGraphQlType
{
    /**
     * Description of the type
     * @return string
     */
    abstract protected function description(): string;

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