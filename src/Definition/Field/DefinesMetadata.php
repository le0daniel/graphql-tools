<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

trait DefinesMetadata
{

    protected mixed $metadata = null;

    final public function withMetadata(mixed $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

}