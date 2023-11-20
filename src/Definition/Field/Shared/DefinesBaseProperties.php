<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use Closure;
use DateTimeInterface;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Utility\Descriptions;

trait DefinesBaseProperties
{
    protected ?string $description = null;
    protected ?string $deprecationReason = null;
    protected ?DateTimeInterface $removalDate = null;
    protected array $tags = [];
    protected Type|Closure $ofType;

    final public function ofType(Type|Closure $resolveType): static
    {
        $clone = clone $this;
        $clone->ofType = $resolveType;
        return $clone;
    }

    private function verifyTypeIsSet(): void {
        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }
    }

    /**
     * @api Define tags for this field or type.
     * @param string ...$tags
     */
    public function tags(string ... $tags): self {
        $clone = clone $this;
        $clone->tags = array_unique($tags);
        return $clone;
    }

    public function getTags(): array {
        return $this->tags;
    }

    public function deprecated(string $reason, ?DateTimeInterface $removalDate = null): static
    {
        $clone = clone $this;
        $clone->deprecationReason = $reason;
        $clone->removalDate = $removalDate;
        return $clone;
    }

    protected function isDeprecated(): bool
    {
        return !empty($this->deprecationReason);
    }

    final public function withDescription(string $description): static
    {
        $clone = clone $this;
        $clone->description = $description;
        return $clone;
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