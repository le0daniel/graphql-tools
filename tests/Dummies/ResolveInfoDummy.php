<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQL\Type\Definition\FieldDefinition;
use \GraphQL\Type\Definition\ResolveInfo as BaseResolveInfo;
use GraphQlTools\Helper\TypeRegistry;
use GraphQlTools\Utility\Reflections;
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
        $fieldDefinition ??= FieldDefinition::create(
            [
                'deprecationReason' => $deprecationReason,
                'name' => 'random',
            ]
        );

        /** @var BaseResolveInfo $instance */
        $instance = $reflection->newInstanceWithoutConstructor();

        // Set the properties via reflection, in case the framework changes to readonly
        Reflections::setProperty($instance, 'path', $path);
        Reflections::setProperty($instance, 'fieldName', 'TestFieldName');
        Reflections::setProperty($instance, 'fieldDefinition', $fieldDefinition);

        return $instance;
    }

}
