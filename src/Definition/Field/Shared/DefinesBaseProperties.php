<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use DateTimeInterface;
use GraphQlTools\Utility\Descriptions;

trait DefinesBaseProperties
{
    protected ?string $description = null;
    protected ?string $deprecationReason = null;
    protected ?DateTimeInterface $removalDate = null;
    protected array $tags = [];

    /**
     * @api Define tags for this field or type.
     * @param string ...$tags
     */
    public function tags(string ... $tags): self {
        $this->tags = $tags;
        return $this;
    }

    public function getTags(): array {
        return array_unique($this->tags);
    }

    public function deprecated(string $reason, ?DateTimeInterface $removalDate = null): static
    {
        $this->deprecationReason = $reason;
        $this->removalDate = $removalDate;
        return $this;
    }

    protected function isDeprecated(): bool
    {
        return !empty($this->deprecationReason);
    }

    final public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    protected function computeDescription(): string
    {
        $baseDescription = $this->description ?? '';
        $withDeprecation = $this->isDeprecated()
            ? Descriptions::pretendDeprecationWarning($baseDescription, $this->deprecationReason, $this->removalDate)
            : $baseDescription;

        return Descriptions::appendTags($withDeprecation, $this->tags);
    }

}