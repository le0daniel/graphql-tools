<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\Shared\DefinesFields;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInputType extends InputObjectType
{
    use DefinesFields, HasDescription;

    private const CLASS_POSTFIX = 'Type';

    final public function __construct(protected readonly TypeRegistry $typeRegistry)
    {
        parent::__construct(
            [
                'name' => static::typeName(),
                'description' => $this->description(),
                'fields' => fn() => $this->initFields(false),
            ]
        );
    }

    private function initField(InputField $fieldDeclaration): ?array {
        $isHidden = $fieldDeclaration->isHidden() || $this->typeRegistry->shouldHideInputField($fieldDeclaration);
        return $isHidden
            ? null
            : $fieldDeclaration->toDefinition($this->typeRegistry);
    }

    abstract protected function fields(): array;

    final function allFields(): array
    {
        return $this->fields();
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
