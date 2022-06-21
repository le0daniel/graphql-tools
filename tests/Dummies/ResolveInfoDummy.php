<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQL\Type\Definition\FieldDefinition;
use \GraphQL\Type\Definition\ResolveInfo as BaseResolveInfo;
use GraphQlTools\Helper\TypeRegistry;

final class ResolveInfoDummy {

    public const DEFAULT_PARENT_TYPE_CLASS = DummyType::class;

    public static function withDefaults(
        ?string $deprecationReason = null,
        ?bool $isBeta = null,
        string $parentTypeClass = self::DEFAULT_PARENT_TYPE_CLASS,
        array $path = ['my-path', 'sub']
    ): BaseResolveInfo{
        $reflection = new \ReflectionClass(BaseResolveInfo::class);

        /** @var BaseResolveInfo $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->path = $path;
        $instance->fieldName = 'TestFieldName';
        $instance->parentType = new $parentTypeClass(new TypeRegistry([]));
        $instance->fieldDefinition = FieldDefinition::create(
            [
                'deprecationReason' => $deprecationReason,
                'isBeta' => $isBeta,
                'name' => 'random',
            ]
        );

        return $instance;
    }

}
