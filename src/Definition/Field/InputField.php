<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;

use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\Shared\DefinesBaseProperties;

final class InputField
{
    use DefinesBaseProperties;

    protected mixed $defaultValue = null;

    final public function __construct(public readonly string $name)
    {
    }

    final public static function withName(string $name): self
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function withDefaultValue(mixed $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @internal This is used internally to get the state of the builder. Do not use this.
     * @return array
     */
    final public function toDefinition(): array
    {
        $defaultValue = isset($this->defaultValue)
            ? ['defaultValue' => $this->defaultValue]
            : [];

        return [
            'name' => $this->name,
            'description' => $this->computeDescription(),
            'type' => $this->ofType,
            'deprecatedReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'tags' => $this->getTags(),
        ] + $defaultValue;
    }
}