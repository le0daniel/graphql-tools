<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field;


use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\DefinitionException;

final class InputField extends BaseProperties
{
    protected mixed $defaultValue = null;

    final public function withDefaultValue(mixed $defaultValue): self
    {
        $clone = clone $this;
        $clone->defaultValue = $defaultValue;
        return $clone;
    }

    /**
     * @return array
     * @throws DefinitionException
     * @internal This is used internally to get the state of the builder. Do not use this.
     */
    final public function toDefinition(TypeRegistry $registry): array
    {
        $defaultValue = isset($this->defaultValue)
            ? ['defaultValue' => $this->defaultValue]
            : [];

        return [
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'type' => $this->getOfType($registry),
            'deprecatedReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
            'tags' => $this->getTags(),
        ] + $defaultValue;
    }
}