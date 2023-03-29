<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Contract\TypeRegistry;

trait DefinesArguments
{
    /** @var InputField[] */
    protected readonly array $inputFields;

    final public function withArguments(InputField ...$arguments): static
    {
        $this->inputFields = $arguments;
        return $this;
    }

    final protected function buildArguments(TypeRegistry $registry, array $excludeTags = []): ?array
    {
        if (!isset($this->inputFields)) {
            return null;
        }

        $inputFields = [];
        foreach ($this->inputFields as $definition) {
            if (!empty($excludeTags) && $definition->containsAnyOfTags(...$excludeTags)) {
                continue;
            }

            $inputFields[] = $definition->toDefinition($registry);
        }

        return $inputFields;
    }

}