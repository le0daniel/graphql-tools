<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use DateTimeInterface;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\TypeRepository;
use JetBrains\PhpStorm\Pure;

abstract class GraphQlField
{
    protected string|null $description = null;
    protected mixed $metadata = null;
    protected bool $isBeta = false;
    protected string|bool $deprecatedReason = false;
    protected DateTimeInterface|null $removalDate = null;

    /** @var Type|callable|string */
    protected mixed $resolveType;

    protected function __construct(public readonly string $name)
    {
    }

    #[Pure]
    final public static function withName(string $name): static
    {
        return new static($name);
    }

    final public function ofType(Type|callable|string $resolveType): static
    {
        $this->resolveType = $resolveType;
        return $this;
    }

    final public function withDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    final public function withMetadata(mixed $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    final public function isDeprecated(string $reason, DateTimeInterface $removalDate): static
    {
        $this->deprecatedReason = $reason;
        $this->removalDate = $removalDate;
        return $this;
    }

    final public function isBeta(): static
    {
        $this->isBeta = true;
        return $this;
    }

    final protected function computeDescription(): ?string
    {
        $descriptionParts = [];

        if ($this->deprecatedReason) {
            $descriptionParts[] = '**DEPRECATED**, Removal Date: ' . $this->removalDate->format('Y-m-d') . '.';
        }

        if ($this->isBeta) {
            $descriptionParts[] = '**BETA**:';
        }

        if ($this->description) {
            $descriptionParts[] = $this->description;
        }

        return empty($descriptionParts) ? null : implode(' ', $descriptionParts);
    }

    final protected function resolveType(TypeRepository $repository, mixed $typeResolver): mixed
    {
        if ($typeResolver instanceof Type) {
            return $typeResolver;
        }

        if (is_callable($typeResolver)) {
            return call_user_func($typeResolver, $repository);
        }

        return $repository->type($typeResolver);
    }

    abstract public function toField(TypeRepository $repository): FieldDefinition;
}