<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

/**
 * @internal
 * As for now, this should not be implemented by any other tool
 * This interface will change.
 */
interface ExtendsGraphQlDefinition
{
    public function typeName(): string;
}