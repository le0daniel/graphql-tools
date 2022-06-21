<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQlTools\Contract\Event;

/**
 * @property-read string $query
 * @method static create(string $query)
 */
final class StartEvent extends Event
{

    protected function __construct(public string $query)
    {
    }

}