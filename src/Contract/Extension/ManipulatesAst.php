<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Extension;

use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;

interface ManipulatesAst
{

    public function visitor(
        Schema $schema,
        ?array $variables,
        TypeInfo $typeInfo,
    ): ?array;

}