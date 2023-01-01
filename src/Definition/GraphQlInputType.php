<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Definition\Field\InputField;
use GraphQlTools\Definition\Shared\Deprecatable;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Classes;

abstract class GraphQlInputType implements DefinesGraphQlType
{
    use InitializesFields, HasDescription, Deprecatable;

    private const CLASS_POSTFIX = 'Type';

    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry): InputObjectType {
        return new InputObjectType([
            'name' => static::typeName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'fields' => fn() => $this->initializeFields($registry, [$this->fields(...)], false),
            'deprecationReason' => $this->deprecationReason,
            'removalDate' => $this->removalDate,
        ]);
    }

    public static function typeName(): string
    {
        $typeName = Classes::baseName(static::class);
        return str_ends_with($typeName, self::CLASS_POSTFIX)
            ? substr($typeName, 0, -strlen(self::CLASS_POSTFIX))
            : $typeName;
    }

}
