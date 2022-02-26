<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\SimpleField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Utility\Fields;

final class FieldMetadataType extends GraphQlType
{

    public static function typeName(): string
    {
        return '__FieldMetadata';
    }

    protected function fields(): array
    {
        return [
            SimpleField::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(static fn(FieldDefinition $definition): string => $definition->name)
            ,
            SimpleField::withName('type')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(static fn(FieldDefinition $definition): string => (string) $definition->getType()),

            SimpleField::withName('metadata')
                ->ofType(MetadataScalar::class)
                ->resolvedBy(static fn(FieldDefinition $definition) => $definition->config[Fields::METADATA_CONFIG_KEY] ?? null),
        ];
    }

    protected function description(): string
    {
        return '';
    }
}