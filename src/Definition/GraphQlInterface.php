<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use Closure;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\ResolvesType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Classes;
use RuntimeException;

abstract class GraphQlInterface extends InterfaceType
{
    use DefinesFields, HasDescription, ResolvesType;

    private const CLASS_POSTFIX = 'Interface';

    /**
     * Return an array of fields of that specific type. The fields
     * are then initialized correctly and a proxy attached to them.
     *
     * @return Field[]
     */
    abstract protected function fields(): array;

    final function allFields(): array
    {
        if (!$this->extendedFields) {
            return $this->fields();
        }

        $fields = $this->fields();
        foreach ($this->extendedFields as $factory) {
            array_push($fields, ...$factory($this->typeRegistry));
        }

        return $fields;
    }

    final public function __construct(protected readonly TypeRegistry $typeRegistry, private readonly ?array $extendedFields = null)
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(true)
            ]
        );
    }

    private function initField(Field $fieldDeclaration): ?FieldDefinition {
        $isHidden = $fieldDeclaration->isHidden() || $this->typeRegistry->shouldHideField($fieldDeclaration);
        return $isHidden
            ? null
            : $fieldDeclaration->toInterfaceDefinition($this->typeRegistry);
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
