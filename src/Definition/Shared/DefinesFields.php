<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlFieldArgument;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Utility\Resolving;

trait DefinesFields {

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return array
     */
    abstract protected function fields(): array;

    /**
     * Ensures the Input fields are correctly defined
     *
     * @return array
     */
    private function initInputFields(): array {
        $fields = [];
        foreach ($this->fields() as $key => $field) {
            if (!$field) {
                continue;
            }

            $field[] = $field instanceof GraphQlFieldArgument
                ? $field->toDefinition(is_string($key) ? $key : null)
                : $field;
        }

        return $fields;
    }

    /**
     * Ensures the fields have the proxy attached to the resolve function
     *
     * @return array
     */
    private function initFields(): array {
        /** @var GraphQlType|GraphQlInterface $this */

        $fields = [];
        foreach ($this->fields() as $key => $field) {
            if (!$field) {
                continue;
            }

            if ($field instanceof GraphQlField) {
                $fields[] = $field->toDefinition(is_string($key) ? $key : null);
                continue;
            }

            // Ensure every field has an attached proxy if necessary
            // This enables extensions to work correctly.
            $fields[$key] = Resolving::attachProxy($field);
        }

        return $fields;
    }

}
