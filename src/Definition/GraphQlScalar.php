<?php

declare(strict_types=1);

namespace GraphQlTools\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQlTools\Contract\DefinesGraphQlType;
use GraphQlTools\Contract\SchemaRules;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Utility\Types;

abstract class GraphQlScalar extends ScalarType implements DefinesGraphQlType
{
    final public function __construct()
    {
        parent::__construct(
            [
                'description' => $this->description(),
                'name' => $this->getName(),
            ]
        );
    }

    public function getName(): string
    {
        return Types::inferNameFromClassName(static::class);
    }

    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): static {
        // Ensure that there is no side effect with internal state
        return clone $this;
    }
}
