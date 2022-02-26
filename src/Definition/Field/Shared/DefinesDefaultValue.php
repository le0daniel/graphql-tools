<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesDefaultValue
{
    protected mixed $defaultValue = null;

    final public function withDefaultValue(mixed $defaultValue): self {
        $this->defaultValue = $defaultValue;
        return $this;
    }
}