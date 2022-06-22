<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesArguments;
use GraphQlTools\Definition\Field\Shared\DefinesField;
use GraphQlTools\Definition\Field\Shared\DefinesMetadata;
use GraphQlTools\Definition\Field\Shared\DefinesReturnType;
use GraphQlTools\Helper\ProxyResolver;
use GraphQlTools\Helper\TypeRegistry;

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

    private function isHidden(TypeRegistry $repository): bool
    {
        return $this->hideFieldBecauseDeprecationDateIsPassed() || $repository->shouldHideField($this);
    }

    final public function toDefinition(TypeRegistry $registry, bool $withoutResolver = false): ?FieldDefinition
    {
        if ($this->isHidden($registry)) {
            return null;
        }

        if (!isset($this->ofType)) {
            throw DefinitionException::fromMissingFieldDeclaration('ofType', $this->name, 'Every field must have a type defined.');
        }

        return FieldDefinition::create([
            'name' => $this->name,
            'resolve' => $withoutResolver ? null : new ProxyResolver($this->resolveFunction ?? null),
            'type' => $this->resolveReturnType($registry),
            'deprecationReason' => $this->computeDeprecationReason(),
            'description' => $this->computeDescription(),
            'args' => $this->buildArguments($registry),
        ]);
    }

    public function resolvedBy(Closure $closure): self
    {
        $this->resolveFunction = $closure;
        return $this;
    }
}