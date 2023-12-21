<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\InputObjectType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Shared\HasFields;

abstract class GraphQlInputType extends TypeDefinition
{
    use HasFields;

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): InputObjectType {
        return new InputObjectType([
            'name' => $this->getName(),
            'description' => $this->computeDescription(),
            'fields' => fn() => $this->initializeFields($registry, [$this->fields(...)], $schemaRules),
            'deprecationReason' => $this->deprecationReason(),
            'removalDate' => $this->removalDate(),
        ]);
    }
}
