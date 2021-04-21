<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Resolver\ProxyResolver;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Resolving;
use GraphQlTools\Utility\Strings;

abstract class GraphQlType extends ObjectType {

    public function __construct(
        protected TypeRepository $typeRepository
    ){
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
                'resolveField' => [ProxyResolver::class, 'default'],
                'interfaces' => $this->interfaces(),
            ]
        );
    }

    /**
     * IMPORTANT: be careful when changing this. For extensions and
     *
     * @return string
     */
    protected static function defaultFieldResolver(): string {

    }

    private function initFields(): array {
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

    abstract protected function fields(): array;

    abstract protected function description(): string;

    protected function interfaces(): array{
        return [];
    }

    public static function typeName(): string {
        $typeName = Strings::baseClassName(static::class);
        return str_ends_with($typeName, 'Type')
            ? substr($typeName, 0, -strlen('Type'))
            : $typeName;
    }

}
