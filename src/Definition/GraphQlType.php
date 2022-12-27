<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\DefinesTypes;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlType extends ObjectType
{
    use DefinesFields, HasDescription, DefinesTypes;

    private const CLASS_POSTFIX = 'Type';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]|callable[]
     */
    abstract protected function fields(): array;

    final function allFields(): array
    {
        return array_merge($this->fields(), $this->extendedFields);
    }

    final public function __construct(protected readonly TypeRegistry $typeRegistry, private readonly array $extendedFields = [])
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(true),
                'interfaces' => fn() => $this->initTypes($this->interfaces()),
            ]
        );
    }

    private function initField(Field $fieldDeclaration): ?FieldDefinition {
        $isHidden = $fieldDeclaration->isHidden() || $this->typeRegistry->shouldHideField($fieldDeclaration);
        return $isHidden
            ? null
            : $fieldDeclaration->toDefinition($this->typeRegistry);
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
