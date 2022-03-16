<?php declare(strict_types=1);

namespace GraphQlTools\CustomIntrospection;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Definition\Field\Argument;
use GraphQlTools\Definition\Field\GraphQlField;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\TypeRepository;
use GraphQlTools\Utility\Fields;
use GraphQlTools\Utility\Types;
use JetBrains\PhpStorm\Pure;
use RuntimeException;

final class TypeMetadataType extends GraphQlType
{
    private const ROOT_QUERY_FIELD_NAME = '__typeMetadata';
    public const TYPE_NAME = '__TypeMetadata';

    #[Pure]
    public static function typeMap(): array
    {
        return [
            TypeMetadataType::typeName() => TypeMetadataType::class,
            MetadataScalar::typeName() => MetadataScalar::class,
            FieldMetadataType::typeName() => FieldMetadataType::class,
        ];
    }

    public static function rootQueryField(TypeRepository $typeRepository): GraphQlField
    {
        return Field::withName(self::ROOT_QUERY_FIELD_NAME)
            ->ofType(self::class)
            ->withDescription('Get extended Metadata for a specific type by its type name.')
            ->withArguments(
                Argument::withName('name')
                    ->ofType(Type::nonNull(Type::string()))
            )
            ->resolvedBy(static function ($data, array $arguments) use ($typeRepository) {
                return Types::enforceTypeLoading($typeRepository->type($arguments['name']));
            });
    }

    protected function fields(): array
    {
        return [
            Field::withName('name')
                ->ofType(Type::nonNull(Type::string()))
                ->resolvedBy(static fn(ObjectType $type): string => $type->name),
            Field::withName('metadata')
                ->ofType(MetadataScalar::class)
                ->resolvedBy(static fn(ObjectType $type): mixed => $type->config[Fields::METADATA_CONFIG_KEY] ?? null),
            Field::withName('fields')
                ->ofType(fn(TypeRepository $typeRepository) => new ListOfType($typeRepository->type(FieldMetadataType::class)))
                ->resolvedBy(static fn(ObjectType $type): array => $type->getFields())
            ,
            Field::withName('fieldByName')
                ->ofType(FieldMetadataType::class)
                ->withArguments(
                    Argument::withName('name')->ofType(Type::nonNull(Type::string()))
                )
                ->resolvedBy(static fn(ObjectType $type, array $arguments): ?FieldDefinition => $type->findField($arguments['name']))
            ,
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