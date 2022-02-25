<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Contract\GraphQlAttribute;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Resolver\ProxyResolver;
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
                'resolve' => static fn(FieldDefinition $definition) => $definition->config[Fields::METADATA_CONFIG_KEY] ?? null,
            ],
            'attributes' => [
                'type' => Type::nonNull(new ListOfType($this->typeRepository->type(MetadataScalar::class))),
                'resolve' => static function (FieldDefinition $definition): array {
                    if (!$definition->resolveFn instanceof ProxyResolver) {
                        return [];
                    }

                    /** @var GraphQlAttribute[] $attributes */
                    $attributes = array_filter(
                        $definition->resolveFn->attributes,
                        static fn($attribute) => $attribute instanceof GraphQlAttribute && $attribute->isExposedPublicly($definition)
                    );

                    return array_map(static fn(GraphQlAttribute $attribute) => $attribute->toIntrospectionMetadata(), $attributes);
                }
            ]
        ];
    }

    protected function description(): string
    {
        return '';
    }
}