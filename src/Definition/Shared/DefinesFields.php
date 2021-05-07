<?php

declare(strict_types=1);

namespace GraphQlTools\Definition\Shared;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlInterface;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Definition\WrappedType;
use GraphQlTools\Utility\Resolving;

use function Sabre\Event\Loop\instance;

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
     * @param array $inputFields
     * @return array
     */
    private function initInputFields(array $inputFields): array {
        $fields = [];
        foreach ($inputFields as $key => $field) {
            if (!$field) {
                continue;
            }

            $fields[$key] = $this->resolveFieldType($field);
        }

        return $fields;
    }

    /**
     * Ensures the fields have the proxy attached to the resolve function
     *
     * @param array $rawFields
     * @return array
     */
    private function initFields(): array {
        /** @var GraphQlType|GraphQlInterface $this */

        $fields = [];
        foreach ($this->fields() as $key => $field) {

            // Skip empty fields. This is useful when using
            // a dynamic schema.
            if (!$field) {
                continue;
            }

            // Attaches the field type from a given type name
            $field = $this->resolveFieldType($field);

            // Ensure the argument types are also resolved.
            if (is_array($field) && isset($field['args'])) {
                $field['args'] = $this->initInputFields($field['args']);
            }

            // Ensure every field has an attached proxy if necessary
            // This enables extensions to work correctly.
            $fields[$key] = Resolving::attachProxy($field);
        }

        return $fields;
    }

    /**
     * Ensure the types are resolved.
     *
     * @param Type|string|array $field
     * @return mixed
     */
    private function resolveFieldType(mixed $field): mixed {
        // If it is a string, we assume it is either a class name or a
        // type name which is resolved by the type repository
        if (is_string($field)) {
            return $this->typeRepository->type($field);
        }

        // We assume it is either already an internal type or something else
        // we can not correctly resolve.
        if (!is_array($field)) {
            return $field instanceof WrappedType
                ? $field->toType($this->typeRepository)
                : $field;
        }

        // If it is a string, we assume it is either a class name or a
        // type name which is resolved
        if (is_string($field['type'])) {
            $field['type'] = $this->typeRepository->type($field['type']);
            return $field;
        }

        // If we have a wrapped type,
        // we resolve it now that we have  access to the type repo
        if ($field['type'] instanceof WrappedType) {
            $field['type'] = $field['type']->toType($this->typeRepository);
            return $field;
        }

        return $field;
    }

}
