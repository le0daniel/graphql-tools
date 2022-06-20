<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\TypeRegistry;

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
        if (isset($this->inputFields)) {
            return null;
        }

        $arguments = [];
        foreach ($this->inputFields as $inputField) {
            if (!$definition = $inputField->toDefinition($registry)) {
                continue;
            }

            $arguments[] = $definition;
        }

        return $arguments;
    }

}