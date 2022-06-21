<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use DateTimeImmutable;
use GraphQlTools\Utility\Lists;

final class ExecutionTrace
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
        Lists::verifyOfType(FieldTrace::class, $this->fieldTraces);
        Lists::verifyOfType(GraphQlError::class, $this->errors);
    }

    public static function from(
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

    public function durationNs(): int
    {
        return $this->endTimeInNanoSeconds - $this->startTimeInNanoSeconds;
    }

}