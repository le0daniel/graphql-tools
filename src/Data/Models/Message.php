<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;


use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Message
 * @package GraphQlTools\Immutable
 *
 * @property-read string $message
 * @property-read string $type
 */
class Message extends Holder
{
    public const TYPE_DEPRECATION = 'deprecation';
    public const TYPE_BETA = 'beta';
    public const TYPE_INFO = 'info';
    public const TYPE_NOTICE = 'notice';

    final public static function from(string $message, string $type){
        return new self([
            'message' => $message,
            'type' => $type
        ]);
    }

    public static function info(string $message): static {
        return self::from($message, self::TYPE_INFO);
    }

    public static function beta(ResolveInfo $resolveInfo, ?string $additionalInformation = null): static {
        $pathString = implode(' > ', $resolveInfo->path);
        $infoMessage = $additionalInformation
            ? "Info: {$additionalInformation}"
            : '';
        return static::from(
            "You used the beta field / argument `{$resolveInfo->fieldName}` at path: `{$pathString}`:" .
            " This field can still change without a notice. Make sure not to use this field in production. {$infoMessage}",
            self::TYPE_BETA
        );
    }

    public static function notice(string $notice): static {
        return self::from(
            $notice,
            self::TYPE_NOTICE
        );
    }

    public static function deprecated(ResolveInfo $info, ?string $reason = null): static {
        $reason ??= $info->fieldDefinition->deprecationReason;
        $pathString = implode(' > ', $info->path);
        return self::from(
            "You used the deprecated field / argument `{$info->fieldName}` at `{$pathString}`: {$reason}",
            self::TYPE_DEPRECATION
        );
    }

}
