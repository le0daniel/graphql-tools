<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Events;

use GraphQL\Executor\ExecutionResult;
use GraphQlTools\Data\ValueObjects\Events\Event;

final class EndEvent extends Event
{

    public function __construct(
        public readonly ExecutionResult $result
    )
    {
        parent::__construct();
    }

    public function hasErrors(): bool
    {
        return count($this->result->errors) > 0;
    }

}