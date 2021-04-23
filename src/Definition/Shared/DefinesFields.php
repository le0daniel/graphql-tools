<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

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

            if (is_array($field) && is_string($field['type'] ?? false)) {
                $field['type'] = $this->typeRepository->type($field['type']);
            } elseif (is_string($field)) {
                $field = $this->typeRepository->type($field);
            }

            $fields[] = $field;
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

            // If we have a string, we assume that the type must be resolved by the Repository.
            // This allows for short definition, Ex
            //
            // 'id' => MyCustomIdType::class,
            // 'parent' => 'Animal' || AnimalType::typeName()
            //
            if (is_array($field) && is_string($field['type'] ?? false)) {
                $field['type'] = $this->typeRepository->type($field['type']);
            } elseif (is_string($field)) {
                $field = $this->typeRepository->type($field);
            }

            // Ensure every field has an attached proxy if necessary
            // This enables extensions to work correctly.
            $fields[$key] = Resolving::attachProxy($field);
        }

        return $fields;
    }

}
