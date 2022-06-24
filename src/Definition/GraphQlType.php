<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\DefinesTypes;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlType extends ObjectType
{
    use DefinesFields, HasDescription, DefinesTypes;

    private const CLASS_POSTFIX = 'Type';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]
     */
    abstract protected function fields(): array;

    final public function __construct(protected readonly TypeRegistry $typeRegistry)
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(),
                'interfaces' => fn() => $this->initTypes($this->interfaces()),
            ]
        );
    }

    private function initFields(): array
    {
        $initializedFields = [];
        foreach ($this->fields() as $fieldDeclaration) {
            if (!$fieldDeclaration instanceof Field) {
                throw DefinitionException::from($fieldDeclaration, Field::class);
            }

            if ($fieldDeclaration->isHidden($this->typeRegistry)) {
                continue;
            }

            $initializedFields[] = $fieldDeclaration->toDefinition($this->typeRegistry);
        }

        return $initializedFields;
    }

    /**
     * Array returning the Interface types resolved by the TypeRepository.
     * @return array
     */
    protected function interfaces(): array
    {
        return [];
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
