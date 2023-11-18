<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Data\ValueObjects\Events\Event;

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