<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQlTools\Utility\Time;

/**
 * @internal
 */
abstract class Event
{
    public readonly int $eventTimeInNanoSeconds;

    public static function create(...$payload): static
    {
        $instance = new static(... $payload);
        $instance->eventTimeInNanoSeconds = Time::nanoSeconds();
        return $instance;
    }

}