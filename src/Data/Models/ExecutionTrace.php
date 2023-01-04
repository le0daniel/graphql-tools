<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use DateTime;
use DateTimeImmutable;
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

    public static function fromSerialized(
        string            $query,
        int               $startTimeInNanoSeconds,
        int               $endTimeInNanoSeconds,
        DateTimeImmutable $startDateTime,
        array             $fieldTraces,
        array             $errors,
    ): self
    {
        return new self(
            $query,
            $startTimeInNanoSeconds,
            $endTimeInNanoSeconds,
            $fieldTraces,
            $errors,
            $startDateTime,
        );
    }

    public function serialize(): string {

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