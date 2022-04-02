<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use DateTimeImmutable;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Data\Models\GraphQlError;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Data\Models\ExecutionTrace;
use GraphQlTools\Data\Models\FieldTrace;
use Closure;

final class Tracing extends Extension
{
    private string $query;

    private DateTimeImmutable $startDateTime;

    private int $startTimeInNanoseconds;
    private int $endTimeInNanoseconds;

    /** @var FieldTrace[] */
    private array $fieldTraces = [];
    /** @var GraphQlError[]  */
    private array $errors = [];

    public function priority(): int
    {
        return -1;
    }

    public function key(): string
    {
        return 'tracing';
    }

    /** @var callable|null */
    private $storeTraceFunction;

    /**
     * @param bool $addTraceToResult
     */
    public function __construct(
        private readonly bool $addTraceToResult = true,
        ?callable $storeTraceFunction = null
    ){
        $this->storeTraceFunction = $storeTraceFunction;
    }

    public function toExecutionTrace(): ExecutionTrace {
        return ExecutionTrace::from(
            $this->query,
            $this->startTimeInNanoseconds,
            $this->endTimeInNanoseconds,
            $this->startDateTime,
            $this->fieldTraces,
            $this->errors
        );
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?ExecutionTrace
    {
        return $this->addTraceToResult
            ? $this->toExecutionTrace()
            : null;
    }

    public function start(StartEvent $startEvent): void
    {
        $this->query = $startEvent->query;
        $this->startTimeInNanoseconds = $startEvent->eventTimeInNanoSeconds;
        $this->startDateTime = new DateTimeImmutable();
    }

    public function end(EndEvent $event): void
    {
        $this->endTimeInNanoseconds = $event->eventTimeInNanoSeconds;
        foreach ($event->result->errors as $error) {
            $this->errors[] = GraphQlError::fromGraphQlError($error);
        }
    }

    public function visitField(VisitFieldEvent $event): Closure
    {
        return function () use ($event) {
            $this->fieldTraces[] = FieldTrace::fromEvent(
                $event,
                $this->startTimeInNanoseconds
            );
        };
    }

    public function __destruct()
    {
        if (!isset($this->startTimeInNanoseconds)) {
            return;
        }

        if ($this->storeTraceFunction) {
            ($this->storeTraceFunction)($this->toExecutionTrace());
        }
    }
}
