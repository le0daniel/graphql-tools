<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

trait DefinesTags
{
    protected array $tags = [];

    /**
     * @api Define tags for this field or type.
     * @param string ...$tags
     * @return DefinesTags
     */
    public function tags(string ... $tags): self {
        $this->tags = $tags;
        return $this;
    }

    public function getTags(): array {
        return array_unique($this->tags);
    }

    public function containsAnyTag(string ...$tags): bool {
        return !empty(array_intersect($this->tags, $tags));
    }
}