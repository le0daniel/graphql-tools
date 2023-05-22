<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use DateTimeImmutable;
use GraphQlTools\Data\ValueObjects\Tracing\ExecutionTrace;
use GraphQlTools\Data\ValueObjects\Tracing\GraphQlError;
use GraphQlTools\Data\ValueObjects\Tracing\ResolverTrace;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Utility\Query;

abstract class Tracing extends Extension
{
    private bool $isIntrospectionQuery = false;
    private string $query;
    private DateTimeImmutable $startDateTime;

    private int $startTimeInNanoseconds;
    private int $endTimeInNanoseconds;

    /** @var ResolverTrace[] */
    private array $resolverTraces = [];

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

    public function isVisibleInResult($context): bool
    {
        return !$this->isIntrospectionQuery;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    abstract protected function storeTrace(ExecutionTrace $trace): void;

    public function toExecutionTrace(): ExecutionTrace
    {
        return new ExecutionTrace(
            $this->query,
            $this->startTimeInNanoseconds,
            $this->endTimeInNanoseconds,
            $this->resolverTraces,
            $this->errors,
            $this->startDateTime,
        );
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?ExecutionTrace
    {
        return $this->toExecutionTrace();
    }

    public function start(StartEvent $event): void
    {
        $this->query = $event->query;
        $this->isIntrospectionQuery = $event->isIntrospectionQuery();
        $this->startTimeInNanoseconds = $event->eventTimeInNanoSeconds;
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
            $this->resolverTraces[] = ResolverTrace::fromEvent(
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

        $this->storeTrace($this->toExecutionTrace());
    }
}
