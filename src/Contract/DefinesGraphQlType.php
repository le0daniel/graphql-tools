<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface DefinesGraphQlType
{
    public function getName(): string;

    /**
     * Returns the definition of the type
     * @internal
     */
    public function toDefinition(TypeRegistry $registry): mixed;
}