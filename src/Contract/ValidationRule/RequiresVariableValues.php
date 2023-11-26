<?php declare(strict_types=1);

namespace GraphQlTools\Contract\ValidationRule;

interface RequiresVariableValues
{
    public function setVariableValues(?array $values): void;
}