<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Data\Models\ExecutionTrace;
use GraphQlTools\Data\Models\FieldTrace;
use Closure;
use GraphQlTools\Utility\QuerySignature;
use GraphQlTools\Utility\Time;

final class Tracing extends Extension
{
    /**
     * String representation of the query to run
     *
     * @var string
     */
    private string $query;

    /**
     * Exact time of when the execution started
     *
     * @var int
     */
    private int $startTimeInNanoseconds;

    /**
     * @var int
     */
    private int $endTimeInNanoseconds;

    /**
     * Array containing all the traces of all fields
     *
     * @var FieldTrace[]
     */
    private array $fieldTraces = [];

    /**
     * Defines the priority of this extension.
     *
     * As this extension requires a high priority and should run first to capture the duration
     * of resolvers correctly, it is set to -1
     *
     * @return int
     */
    public function priority(): int
    {
        return -1;
    }

    /**
     * Defines the key of the extension in the extension result array.
     *
     * @return string
     */
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

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?ExecutionTrace
    {
        return $this->addTraceToResult
            ? ExecutionTrace::from(
                $this->query,
                $this->startTimeInNanoseconds,
                $this->endTimeInNanoseconds,
                $this->fieldTraces
            )
            : null;
    }

    public function start(StartEvent $startEvent): void
    {
        $this->query = $startEvent->query;
        $this->startTimeInNanoseconds = $startEvent->eventTimeInNanoSeconds;
    }

    public function end(EndEvent $event): void
    {
        $this->endTimeInNanoseconds = $event->eventTimeInNanoSeconds;
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
            ($this->storeTraceFunction)(
                ExecutionTrace::from(
                    $this->query,
                    $this->startTimeInNanoseconds,
                    $this->endTimeInNanoseconds,
                    $this->fieldTraces
                )
            );
        }
    }
}
