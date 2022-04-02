<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Error\FormattedError;
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
    private string $query;
    private int $startTimeInNanoseconds;
    private int $endTimeInNanoseconds;

    /** @var FieldTrace[] */
    private array $fieldTraces = [];
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
                $this->fieldTraces,
                $this->errors
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
        foreach ($event->result->errors as $error) {
            $this->errors[] = FormattedError::createFromException($error) ;
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
