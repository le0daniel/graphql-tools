<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface DefinesGraphQlType
{
    public function toDefinition(TypeRegistry $registry): mixed;
}