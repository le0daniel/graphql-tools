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
use GraphQlTools\Data\Models\ResolverTrace;
use Closure;
use GraphQlTools\Utility\Query;

final class Tracing extends Extension
{
    private bool $isIntrospectionQuery = false;
    private string $query;
    private DateTimeImmutable $startDateTime;

    private int $startTimeInNanoseconds;
    private int $endTimeInNanoseconds;

    /** @var ResolverTrace[] */
    private array $fieldTraces = [];

    /** @var GraphQlError[] */
    private array $errors = [];

    public function priority(): int
    {
        return -1;
    }

    public function key(): string
    {
        return 'tracing';
    }

    public function isVisibleInResult(): bool
    {
        return $this->addTraceToResult && !$this->isIntrospectionQuery;
    }

    /**
     * @param bool $addTraceToResult
     */
    public function __construct(
        private readonly bool     $addTraceToResult = true,
        private readonly ?Closure $storeTraceFunction = null
    )
    {
    }

    public function toExecutionTrace(): ExecutionTrace
    {
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
        return $this->toExecutionTrace();
    }

    public function start(StartEvent $startEvent): void
    {
        $this->query = $startEvent->query;
        $this->isIntrospectionQuery = Query::isIntrospection($startEvent->query);
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

    public function visitField(VisitFieldEvent $event): ?Closure
    {
        if ($this->isIntrospectionQuery) {
            return null;
        }

        return function () use ($event) {
            $this->fieldTraces[] = ResolverTrace::fromEvent(
                $event,
                $this->startTimeInNanoseconds
            );
        };
    }

    public function __destruct()
    {
        if (!isset($this->startTimeInNanoseconds) || $this->isIntrospectionQuery) {
            return;
        }

        if ($this->storeTraceFunction) {
            ($this->storeTraceFunction)($this->toExecutionTrace());
        }
    }
}
