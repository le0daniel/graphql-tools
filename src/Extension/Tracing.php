<?php

declare(strict_types=1);

namespace GraphQlTools\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Immutable\ExecutionTrace;
use GraphQlTools\Immutable\ResolverTrace;
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
        mixed $storeTraceInformation = null
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

    public function start(int $eventTimeInNanoseconds, string $query): void
    {
        $this->startTime = new DateTimeImmutable();
        $this->startTimeInNanoseconds = $eventTimeInNanoseconds;
    }

    public function end(int $eventTimeInNanoseconds): void
    {
        $this->endTime = new DateTimeImmutable();
        $this->endTimeInNanoseconds = $eventTimeInNanoseconds;
    }

    /**
     * @param int $eventTimeInNanoseconds
     * @param $typeData
     * @param array $arguments
     * @param ResolveInfo $info
     * @return Closure(mixed $value): void
     */
    public function fieldResolution(int $eventTimeInNanoseconds, $typeData, array $arguments, ResolveInfo $info): Closure
    {
        return function () use ($eventTimeInNanoseconds, $info): void {

            // Add trace as soon as the field is resolved
            $this->fieldTraces[] = ResolverTrace::fromResolveInfo(
                $info,
                $eventTimeInNanoseconds,
                $this->startTimeInNanoseconds
            );
        };
    }
}
