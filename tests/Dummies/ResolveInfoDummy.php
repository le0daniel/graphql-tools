<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQL\Type\Definition\FieldDefinition;
use \GraphQL\Type\Definition\ResolveInfo as BaseResolveInfo;
use ReflectionClass;

final class ResolveInfoDummy
{

    public const DEFAULT_PARENT_TYPE_CLASS = DummyType::class;

    public static function withDefaults(
        ?string $deprecationReason = null,
        string  $parentTypeClass = self::DEFAULT_PARENT_TYPE_CLASS,
        array   $path = ['my-path', 'sub'],
        ?FieldDefinition $fieldDefinition = null,
    ): BaseResolveInfo
    {
        $reflection = new ReflectionClass(BaseResolveInfo::class);
        $fieldDefinition ??= new FieldDefinition(
            [
                'deprecationReason' => $deprecationReason,
                'name' => 'random',
            ]
        );

        /** @var BaseResolveInfo $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->path = $path;
        $instance->fieldName = 'TestFieldName';
        $instance->fieldDefinition = $fieldDefinition;

        return $instance;
    }

}
