<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use DateTimeImmutable;
use GraphQL\Error\DebugFlag;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Printer;
use GraphQlTools\Contract\Events\VisitField;
use GraphQlTools\Contract\Extension\InteractsWithFieldResolution;
use GraphQlTools\Contract\Extension\ListensToLifecycleEvents;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Data\ValueObjects\Tracing\ExecutionTrace;
use GraphQlTools\Data\ValueObjects\Tracing\GraphQlError;
use GraphQlTools\Data\ValueObjects\Tracing\ResolverTrace;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\FieldResolution;
use GraphQlTools\Utility\Query;

class Tracing extends Extension implements InteractsWithFieldResolution, ProvidesResultExtension
{
    private bool $isIntrospectionQuery = false;
    private string|DocumentNode $query;
    private DateTimeImmutable $startDateTime;

    private int $startTimeInNanoseconds;
    private int $endTimeInNanoseconds;

    /** @var ResolverTrace[] */
    private array $resolverTraces = [];

    /** @var GraphQlError[] */
    private array $errors = [];

    public function priority(): int
    {
        return 1;
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

    public function toExecutionTrace(): ExecutionTrace
    {
        return new ExecutionTrace(
            $this->queryAsString(),
            $this->startTimeInNanoseconds,
            $this->endTimeInNanoseconds,
            $this->resolverTraces,
            $this->errors,
            $this->startDateTime,
        );
    }

    private function queryAsString(): string {
        return $this->query instanceof DocumentNode
            ? Printer::doPrint($this->query)
            : $this->query;
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

    public function visitField(VisitField $event): void
    {
        if ($this->isIntrospectionQuery) {
            return;
        }

        $event->then(function () use ($event) {
            $this->resolverTraces[] = ResolverTrace::fromEvent(
                $event,
                $this->startTimeInNanoseconds
            );
        });
    }

    public function serialize(int $debug = DebugFlag::NONE): mixed
    {
        return $this->toExecutionTrace();
    }
}
