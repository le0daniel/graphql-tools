<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use GraphQL\Type\Definition\FieldDefinition;

class Fields
{
    public const BETA_FIELD_CONFIG_KEY = 'isBeta';
    public const NOTICE_CONFIG_KEY = 'notice';
    public const METADATA_CONFIG_KEY = '__custom_metadata';

    final public static function isBetaField(FieldDefinition $definition): bool
    {
        return ($definition->config[self::BETA_FIELD_CONFIG_KEY] ?? false) === true;
    }

    final public static function getFieldNotice(FieldDefinition $definition): ?string {
        return $definition->config[self::NOTICE_CONFIG_KEY] ?? null;
    }
}