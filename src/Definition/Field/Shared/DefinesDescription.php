<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use DateTimeInterface;

trait DefinesDescription
{
    protected ?string $description = null;

    final public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

}