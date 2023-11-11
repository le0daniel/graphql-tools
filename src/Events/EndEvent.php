<?php declare(strict_types=1);

namespace GraphQlTools\Events;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Contract\Event;

/**
 * @method static create(ExecutionResult $result)
 */
final class EndEvent extends Event
{

    protected function __construct(
        public readonly ExecutionResult $result
    )
    {
    }

    public function hasErrors(): bool
    {
        return count($this->result->errors) > 0;
    }

}