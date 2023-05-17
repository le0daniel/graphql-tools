<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use GraphQlTools\Data\ValueObjects\Tracing\ResolverTrace;
use GraphQlTools\Utility\Time;
use GraphQlTools\Utility\Typing;
use JsonSerializable;

final class ExecutionTrace implements JsonSerializable
{
    public function __construct(
        public readonly string            $query,
        public readonly int               $startTimeInNanoSeconds,
        public readonly int               $endTimeInNanoSeconds,
        public readonly array             $fieldTraces,
        public readonly array             $errors,
        public readonly DateTimeImmutable $startDateTime,
    )
    {
        Typing::verifyListOfType(ResolverTrace::class, $this->fieldTraces);
        Typing::verifyListOfType(GraphQlError::class, $this->errors);
    }

    public function endDateTime(): DateTimeImmutable {
        $durationInMicroseconds = (int) Time::nanoSecondsToMicroseconds($this->durationNs(), 0);
        return $this->startDateTime->add(DateInterval::createFromDateString("{$durationInMicroseconds} microseconds"));
    }

    public function durationNs(): int
    {
        return $this->endTimeInNanoSeconds - $this->startTimeInNanoSeconds;
    }

    public function jsonSerialize(): array
    {
        return [
            'version' => 1,
            'startTime' => $this->startDateTime->format(DateTime::RFC3339_EXTENDED),
            'endTime' => '',
            'duration' => $this->durationNs(),
            'execution' => [
                'resolvers' => $this->fieldTraces,
            ]
        ];
    }
}