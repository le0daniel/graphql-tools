<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Utility\Typing;

trait DefinesFields
{
    private function initInputFields(array $inputFields): array
    {
        $initializedInputFields = [];

        /** @var InputField $inputField */
        foreach ($inputFields as $inputField) {
            if (!$inputField) {
                continue;
            }

            Typing::verifyOfType(InputField::class, $inputField);
            if ($inputField->isHidden() || $this->typeRegistry->shouldHideInputField($inputField)) {
                continue;
            }

            $initializedInputFields[] = $inputField->toDefinition($this->typeRegistry);
        }

        return $initializedInputFields;
    }
}
