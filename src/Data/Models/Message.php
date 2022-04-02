<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;


use GraphQL\Type\Definition\ResolveInfo;
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

    public static function info(string $message): static
    {
        return new self([
            'message' => $message,
            'type' => self::TYPE_INFO,
        ]);
    }

    public static function beta(ResolveInfo $resolveInfo, ?string $additionalInformation = null): static
    {
        $pathString = Paths::toString($resolveInfo->path);
        $infoMessage = $additionalInformation
            ? "**Info**: {$additionalInformation}"
            : '';
        return new self([
            'message' => "You used the beta field / argument `{$resolveInfo->fieldName}` at path: `{$pathString}`:" .
                " This field can still change without a notice. Make sure **not** to use this field / argument in production. {$infoMessage}",
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

    public static function deprecated(ResolveInfo $info, ?string $reason = null): static
    {
        $reason ??= $info->fieldDefinition->deprecationReason;
        $pathString = implode(' > ', $info->path);

        return new self([
            'message' => "You used the deprecated field / argument `{$info->fieldName}` at `{$pathString}`: {$reason}",
            'type' => self::TYPE_DEPRECATION
        ]);
    }
}
