<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\DefinitionException;
use GraphQlTools\Definition\Field\InputField;

trait DefinesFields
{
    private function initInputFields(array $inputFields): array
    {
        $initializedInputFields = [];
        foreach ($inputFields as $inputField) {
            if (!$inputField) {
                continue;
            }

            if (!$inputField instanceof InputField) {
                throw DefinitionException::from($inputField, InputField::class);
            }

            if ($inputField->isHidden($this->typeRegistry)) {
                continue;
            }

            $initializedInputFields[] = $inputField->toDefinition($this->typeRegistry);
        }

        return $initializedInputFields;
    }
}
