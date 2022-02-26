<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use DateTimeInterface;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\TypeRepository;
use JetBrains\PhpStorm\Pure;

abstract class GraphQlField
{
    use HasDescription, HasType;

    protected mixed $metadata = null;

    protected function __construct(public readonly string $name)
    {
    }

    #[Pure]
    final public static function withName(string $name): static
    {
        return new static($name);
    }

    final public function withMetadata(mixed $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    abstract public function toField(TypeRepository $repository): FieldDefinition;
}