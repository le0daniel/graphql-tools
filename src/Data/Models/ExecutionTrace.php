<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

use DateTimeImmutable;

/**
 * @property-read string $query
 * @property-read int $startTimeInNanoSeconds
 * @property-read int $endTimeInNanoSeconds
 * @property-read int $durationNs
 * @property-read FieldTrace[]
 * @property-read GraphQlError[] $errors
 * @property-read DateTimeImmutable $startDateTime
 */
final class ExecutionTrace extends Holder
{
    public static function from(
        string            $query,
        int               $startTimeInNanoSeconds,
        int               $endTimeInNanoSeconds,
        DateTimeImmutable $startDateTime,
        array             $fieldTraces,
        array             $errors,
    ): self
    {
        Holder::verifyListOfInstances(FieldTrace::class, $fieldTraces);
        Holder::verifyListOfInstances(GraphQlError::class, $errors);

        return new self([
            'query' => $query,
            'startTimeInNanoSeconds' => $startTimeInNanoSeconds,
            'endTimeInNanoSeconds' => $endTimeInNanoSeconds,
            'startDateTime' => $startDateTime,
            'fieldTraces' => $fieldTraces,
            'errors' => $errors,
        ]);
    }

    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'durationNs' => $this->endTime - $this->startTime,
            default => parent::getValue($name),
        };
    }

}