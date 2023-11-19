<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface ExtendType
{
    public function typeName(): string;
    public function getFields(TypeRegistry $registry): array;
}