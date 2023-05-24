<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Shared\HasDeprecation;
use GraphQlTools\Definition\Shared\HasDescription;
use GraphQlTools\Definition\Shared\InitializesFields;
use GraphQlTools\Utility\Types;

abstract class GraphQlInputType implements DefinesGraphQlType
{
    use InitializesFields, HasDescription, HasDeprecation;

    abstract protected function fields(TypeRegistry $registry): array;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): InputObjectType {
        return new InputObjectType([
            'name' => $this->getName(),
            'description' => $this->addDeprecationToDescription($this->description()),
            'fields' => fn() => $this->initializeFields($registry, [$this->fields(...)], $schemaRules),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
        ]);
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }
}
