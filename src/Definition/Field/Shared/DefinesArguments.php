<?php declare(strict_types=1);

namespace GraphQlTools\Definition\Field\Shared;

use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Helper\TypeRegistry;

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

        $arguments = [];
        foreach ($this->inputFields as $inputField) {
            if ($inputField->isHidden($registry)) {
                continue;
            }

            $arguments[] = $inputField->toDefinition($registry);
        }

        return $arguments;
    }

}