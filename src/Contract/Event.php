<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQlTools\Utility\Time;

abstract class Event
{

    public function __construct(public int $eventTimeInNanoSeconds)
    {
    }

    public static function create(...$payload): static {
        return new static(Time::nanoSeconds(), ... $payload);
    }

}