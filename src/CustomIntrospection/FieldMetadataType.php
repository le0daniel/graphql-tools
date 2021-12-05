<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;

final class FieldMetadataType extends GraphQlType
{

    public static function typeName(): string
    {
        return '__FieldMetadata';
    }

    protected function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => static fn(FieldDefinition $definition): string => $definition->name,
            ],
            'type' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => static fn(FieldDefinition $definition): string => (string) $definition->getType(),
            ],
            'metadata' => [
                'type' => MetadataScalar::class,
                'resolve' => static fn(FieldDefinition $definition) => $definition->config[GraphQlField::METADATA_CONFIG_KEY] ?? null,
            ]
        ];
    }

    protected function description(): string
    {
        return '';
    }
}