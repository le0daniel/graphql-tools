<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Utility\Fields;


class Field
{
    use DefinesField, DefinesReturnType, DefinesArguments, DefinesMetadata;
    private ?Closure $resolveFunction = null;

    final protected function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): static
    {
        return new self($name);
    }

    final public function isHidden(): bool
    {
        return $this->hideFieldBecauseDeprecationDateIsPassed();
    }

    final public function toInterfaceDefinition(TypeRegistry $registry): FieldDefinition {
        $this->verifyTypeIsSet();
        return FieldDefinition::create([
            'name' => $this->name,
            'type' => $this->resolveReturnType($registry),
            'deprecationReason' => $this->computeDeprecationReason(),
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($registry),
            Fields::METADATA_CONFIG_KEY => $this->metadata
        ]);
    }

    final public function toDefinition(TypeRegistry $registry): FieldDefinition
    {
        $this->verifyTypeIsSet();
        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => new ProxyResolver($this->resolveFunction ?? null),
            'type' => $this->resolveReturnType($registry),
            'deprecationReason' => $this->computeDeprecationReason(),
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($registry),
            Fields::METADATA_CONFIG_KEY => $this->metadata
        ]);
    }

    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }
}