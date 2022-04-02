<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Contract\Event;

/**
 * @method static create(ExecutionResult $result)
 */
final class EndEvent extends Event
{

    public function __construct(int $eventTimeInNanoSeconds, public readonly ExecutionResult $result)
    {
        parent::__construct($eventTimeInNanoSeconds);
    }

    public function hasErrors(): bool {
        return count($this->result->errors) > 0;
    }

}