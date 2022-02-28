<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Data\Models\ExecutionTrace;
use GraphQlTools\Data\Models\ResolverTrace;
use Closure;

final class Tracing extends Extension
{

    private const VERSION = 1;

    /**
     * Datetime object of when the graphql execution started
     *
     * @var \DateTimeImmutable
     */
    private DateTimeImmutable $startTime;

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
     * @var \DateTimeImmutable
     */
    private DateTimeImmutable $endTime;

    /**
     * @var int
     */
    private int $endTimeInNanoseconds;

    /**
     * Array containing all the traces of all fields
     *
     * @var ResolverTrace[]
     */
    private array $fieldTraces = [];

    /** @var null|callable  */
    protected $storeTraceInformation = null;

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

    /**
     * @param bool $addTraceToResult
     * @param callable|null $storeTraceInformation
     */
    public function __construct(
        private bool $addTraceToResult = false,
        ?callable $storeTraceInformation = null
    )
    {
        $this->storeTraceInformation = $storeTraceInformation;
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?ExecutionTrace
    {
        $executionTrace = ExecutionTrace::from(
            self::VERSION,
            $this->query,
            $this->startTime,
            $this->endTime,
            $this->endTimeInNanoseconds,
            $this->fieldTraces
        );

        if ($this->storeTraceInformation) {
            call_user_func($this->storeTraceInformation, $executionTrace);
        }

        return $this->addTraceToResult
            ? $executionTrace
            : null;
    }

    public function start(StartEvent $event): void
    {
        $this->query = $event->query;
        $this->startTime = new DateTimeImmutable();
        $this->startTimeInNanoseconds = $event->eventTimeInNanoSeconds;
    }

    public function end(EndEvent $event): void
    {
        $this->endTime = new DateTimeImmutable();
        $this->endTimeInNanoseconds = $event->eventTimeInNanoSeconds;
    }

    public function visitField(FieldResolutionEvent $event): Closure
    {
        return function ($resolvedValue) use ($event): mixed {
            $this->fieldTraces[] = ResolverTrace::fromEvent(
                $event,
                $this->startTimeInNanoseconds
            );
            return $resolvedValue;
        };
    }
}
