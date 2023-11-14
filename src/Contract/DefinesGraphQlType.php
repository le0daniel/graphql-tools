<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQlTools\Definition\DefinitionException;

interface DefinesGraphQlType
{
    public function getName(): string;

    /**
     * Returns the definition of the type
     * @throws DefinitionException
     * @internal
     */
    public function toDefinition(TypeRegistry $registry, SchemaRules $schemaRules): mixed;
}