<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesDefaultValue
{
    protected readonly mixed $defaultValue;

    final public function withDefaultValue(mixed $defaultValue): self {
        $this->defaultValue = $defaultValue;
        return $this;
    }
}