<?php declare(strict_types=1);

namespace GraphQlTools\Contract;



use GraphQL\Type\Definition\FieldDefinition;

abstract class GraphQlAttribute
{
    public function isExposedPublicly(FieldDefinition $fieldDefinition): bool {
        return false;
    }

    public function toIntrospectionMetadata(): mixed {
        return null;
    }
}