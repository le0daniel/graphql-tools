<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesMetadata
{

    public readonly mixed $metadata;

    public function withMetadata(mixed $metadata): self {
        $this->metadata = $metadata;
        return $this;
    }

    final protected function initializeMetadataOnce(): void {
        if (!isset($this->metadata)) {
            $this->metadata = null;
        }
    }

}