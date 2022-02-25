<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\GraphQlField;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;
use GraphQlTools\Utility\Types;
use JetBrains\PhpStorm\Pure;

final class TypeMetadataType extends GraphQlType
{
    private const ROOT_QUERY_FIELD_NAME = '__typeMetadata';
    public const TYPE_NAME = '__TypeMetadata';

    #[Pure]
    public static function typeMap(): array {
        return [
            TypeMetadataType::typeName() => TypeMetadataType::class,
            MetadataScalar::typeName() => MetadataScalar::class,
            FieldMetadataType::typeName() => FieldMetadataType::class,
        ];
    }

    public static function rootQueryField(TypeRepository $typeRepository): FieldDefinition {
        return FieldDefinition::create([
            'name' => self::ROOT_QUERY_FIELD_NAME,
            'description' => 'Get extended Metadata for a specific type by its type name.',
            'deprecationReason' => 'This should only be used to get additional data for types.',
            'type' => $typeRepository->type(self::class),
            'args' => [
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                ]
            ],
            'resolve' => static function ($data, array $arguments) use ($typeRepository) {
                try {
                    return Types::enforceTypeLoading($typeRepository->type($arguments['name']));
                } catch (\Throwable) {
                    return null;
                }
            },
        ]);
    }

    protected function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => static fn(ObjectType $type): string => $type->name
            ],
            'metadata' => [
                'type' => MetadataScalar::class,
                'resolve' => static fn(ObjectType $type): mixed => $type->config[Fields::METADATA_CONFIG_KEY] ?? null,
            ],
            'fields' => [
                'type' => $this->typeRepository->listOfType(FieldMetadataType::class),
                'resolve' => static fn(ObjectType $type): array => $type->getFields()
            ],
            'fieldByName' => [
                'type' => FieldMetadataType::class,
                'args' => [
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                    ]
                ],
                'resolve' => static fn(ObjectType $type, array $arguments): ?FieldDefinition => $type->findField($arguments['name']),
            ]
        ];
    }

    protected function description(): string
    {
        return 'Get extended Metadata for a specific type by its type name.';
    }

    public static function typeName(): string
    {
        return self::TYPE_NAME;
    }
}