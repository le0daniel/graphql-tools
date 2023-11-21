<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use DateTimeInterface;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Utility\Descriptions;

abstract class BaseProperties
{
    protected ?string $description = null;
    protected ?string $deprecationReason = null;
    protected ?DateTimeInterface $removalDate = null;
    protected array $tags = [];
    protected Type|Closure $ofType;

    final public function __construct(public readonly string $name)
    {
    }

    public static function withName(string $name): static
    {
        return new static($name);
    }

    public function ofType(Type|Closure $resolveType): static
    {
        $clone = clone $this;
        $clone->ofType = $resolveType;
        return $clone;
    }

    protected function verifyTypeIsSet(): void
    {
        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string ...$tags
     * @api Define tags for this field or type.
     */
    public function tags(string ...$tags): static
    {
        $clone = clone $this;
        $clone->tags = array_unique($tags);
        return $clone;
    }

    public function deprecated(string $reason, ?DateTimeInterface $removalDate = null): static
    {
        $clone = clone $this;
        $clone->deprecationReason = $reason;
        $clone->removalDate = $removalDate;
        return $clone;
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
        $withDeprecation = !empty($this->deprecationReason)
            ? Descriptions::pretendDeprecationWarning($baseDescription, $this->deprecationReason, $this->removalDate)
            : $baseDescription;

        return Descriptions::appendTags($withDeprecation, $this->tags);
    }

}