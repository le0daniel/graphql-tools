<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use DateTimeInterface;

final class Descriptions
{
    public static function pretendDeprecationWarning(string $description, string $reason, ?DateTimeInterface $removalDate = null): string {
        return $removalDate
            ? "**Deprecated**: {$reason} | Removal Date: {$removalDate->format('Y-m-d')}. {$description}"
            : "**Deprecated**: {$reason}. No removal date specified. {$description}";
    }

    public static function appendTags(string $description, array $tags): string {
        return empty($tags)
            ? $description
            : $description . PHP_EOL . PHP_EOL . 'Tags: ' . implode(', ', $tags);
    }

}