<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesMetadata
{
    public mixed $metadata = null;
    public mixed $schemaVariant = null;

    final public function ofSchemaVariant(mixed $variant): static
    {
        $this->schemaVariant = $variant;
        return $this;
    }

    public function withMetadata(mixed $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

}