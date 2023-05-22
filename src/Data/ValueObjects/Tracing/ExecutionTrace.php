<?php declare(strict_types=1);

namespace GraphQlTools\Data\ValueObjects\Tracing;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use GraphQlTools\Utility\Time;
use JsonSerializable;

final class ExecutionTrace implements JsonSerializable
{
    /**
     * @param string $query
     * @param int $startTimeInNanoSeconds
     * @param int $endTimeInNanoSeconds
     * @param array<ResolverTrace> $resolverTraces
     * @param array<GraphQlError> $errors
     * @param DateTimeImmutable $startDateTime
     */
    public function __construct(
        public readonly string            $query,
        public readonly int               $startTimeInNanoSeconds,
        public readonly int               $endTimeInNanoSeconds,
        public readonly array             $resolverTraces,
        public readonly array             $errors,
        public readonly DateTimeImmutable $startDateTime,
    )
    {
    }

    public function endDateTime(): DateTimeImmutable {
        $durationInMicroseconds = (int) Time::nanoSecondsToMicroseconds($this->durationInNanoseconds(), 0);
        return $this->startDateTime->add(DateInterval::createFromDateString("{$durationInMicroseconds} microseconds"));
    }

    public function durationInNanoseconds(): int
    {
        return $this->endTimeInNanoSeconds - $this->startTimeInNanoSeconds;
    }

    public function jsonSerialize(): array
    {
        return [
            'version' => 1,
            'startTime' => $this->startDateTime->format(DateTime::RFC3339_EXTENDED),
            'endTime' => $this->endDateTime()->format(DateTime::RFC3339_EXTENDED),
            'duration' => $this->durationInNanoseconds(),
            'execution' => [
                'resolvers' => $this->resolverTraces,
            ]
        ];
    }
}