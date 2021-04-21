<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Strings;

abstract class GraphQlInputType extends InputObjectType {

    public function __construct(protected TypeRepository $typeRepository){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
            ]
        );
    }

    private function initFields(): array {
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

    abstract protected function fields(): array;
    abstract protected function description(): string;

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Type')
            ? substr($typeName, 0, -strlen('Type'))
            : $typeName;
    }

}
