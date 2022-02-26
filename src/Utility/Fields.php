<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQlTools\Definition\Field\GraphQlField;
use ReflectionClass;

class Fields
{
    public const BETA_FIELD_CONFIG_KEY = 'isBeta';
    public const NOTICE_CONFIG_KEY = 'notice';
    public const METADATA_CONFIG_KEY = '__custom_metadata';

    final public static function guessFieldName(mixed $name): ?string
    {
        return is_string($name) ? $name : null;
    }

    final public static function isFieldClass(string $className): bool
    {
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf(GraphQlField::class);
    }

    final public static function isBetaField(FieldDefinition $definition): bool
    {
        return ($definition->config[self::BETA_FIELD_CONFIG_KEY] ?? false) === true;
    }

    final public static function getFieldNotice(FieldDefinition $definition): ?string {
        return $definition->config[self::NOTICE_CONFIG_KEY] ?? null;
    }
}