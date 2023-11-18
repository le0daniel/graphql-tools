<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQlTools\Data\ValueObjects\Events\Event;

/**
 * @method static create(string $query, string|null $operationName)
 */
final class ParsedEvent extends Event
{
    public function __construct(
        public readonly string      $query,
        public readonly null|string $operationName,
    )
    {
    }
}