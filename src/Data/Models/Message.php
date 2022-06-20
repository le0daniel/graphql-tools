<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;


use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

/**
 * Class Message
 * @package GraphQlTools\Immutable
 *
 * @property-read string $message
 * @property-read string $type
 */
final class Message implements JsonSerializable
{
    public function __construct(public readonly string $message, public readonly string $type)
    {
    }

    public static function deprecatedArgument(string $fieldName, string $parentName, string $argumentName, string $reason): static
    {
        return new self("Deprecated argument `{$argumentName}` used at `{$parentName}.{$fieldName}`: {$reason}", 'deprecation');
    }

    public static function deprecated(string $fieldName, Type|string|null $parent, string $reason): static
    {
        $parentName = is_string($parent)
            ? $parent
            : $parent?->name;
        $isOnInterface = $parent instanceof InterfaceType;

        return new self(
            $isOnInterface
                ? "Deprecated field used on interface at `{$parentName}.{$fieldName}`: {$reason}"
                : "Deprecated field used at `{$parentName}.{$fieldName}`: {$reason}",
            'deprecation'
        );
    }

    #[ArrayShape(['message' => "string", 'type' => "string"])]
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}
