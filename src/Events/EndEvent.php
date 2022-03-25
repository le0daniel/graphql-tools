<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQL\Error\Error;
use GraphQlTools\Contract\Event;

/**
 * @property-read Error[] $graphQlErrors
 * @method static create(Error[] $graphQlErrors)
 */
final class EndEvent extends Event
{

    public function __construct(int $eventTimeInNanoSeconds, public readonly array $graphQlErrors)
    {
        parent::__construct($eventTimeInNanoSeconds);
    }

    public function hasErrors(): bool {
        return count($this->graphQlErrors) > 0;
    }

}