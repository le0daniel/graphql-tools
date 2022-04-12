<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;


use GraphQL\Type\Definition\CompositeType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQlTools\Utility\Paths;
use JsonSerializable;

/**
 * Class Message
 * @package GraphQlTools\Immutable
 *
 * @property-read string $message
 * @property-read string $type
 */
final class Message extends Holder
{
    public const TYPE_DEPRECATION = 'deprecation';
    public const TYPE_BETA = 'beta';
    public const TYPE_INFO = 'info';
    public const TYPE_NOTICE = 'notice';

    public static function beta(string $fieldName, string $parentName): static
    {
        return new self([
            'message' =>
                "You used the beta field `{$parentName}.{$fieldName}`: " .
                "This field can still change without a notice. Make sure **not** to use this field / argument in production.",
            'type' => self::TYPE_BETA,
        ]);
    }

    public static function notice(string $notice): static
    {
        return new self([
            'message' => $notice,
            'type' => self::TYPE_NOTICE
        ]);
    }

    public static function deprecatedEnumValue(string $enumName, string $valueName, string $reason): static
    {
        return new self([
            'message' => "Deprecated enum value used `{$enumName}.{$valueName}`: $reason",
            'type' => self::TYPE_DEPRECATION
        ]);
    }

    public static function deprecatedArgument(string $fieldName, string $parentName, string $argumentName, string $reason): static
    {
        return new self([
            'message' => "Deprecated argument `{$argumentName}` used at `{$parentName}.{$fieldName}`: {$reason}",
            'type' => self::TYPE_DEPRECATION
        ]);
    }

    public static function deprecated(string $fieldName, Type|string|null $parent, string $reason): static
    {
        $parentName = is_string($parent)
            ? $parent
            : $parent?->name;
        $isOnInterface = $parent instanceof InterfaceType;

        return new self([
            'message' => $isOnInterface
                ? "Deprecated field used on interface at `{$parentName}.{$fieldName}`: {$reason}"
                : "Deprecated field used at `{$parentName}.{$fieldName}`: {$reason}",
            'type' => self::TYPE_DEPRECATION
        ]);
    }
}
