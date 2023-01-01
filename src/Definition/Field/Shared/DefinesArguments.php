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

    final protected function buildArguments(TypeRegistry $registry): ?array
    {
        if (!isset($this->inputFields)) {
            return null;
        }

        return array_map(fn(InputField $inputField): array => $inputField->toDefinition($registry), $this->inputFields);
    }

}