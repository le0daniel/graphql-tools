<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQlTools\Utility\Time;

/**
 * @internal
 */
abstract class Event
{
    public readonly int $eventTimeInNanoSeconds;

    public function __construct()
    {
        $this->eventTimeInNanoSeconds = Time::nanoSeconds();
    }
}