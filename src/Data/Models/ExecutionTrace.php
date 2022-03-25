<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

/**
 * @property-read string $query
 * @property-read int $startTime
 * @property-read int $endTime
 * @property-read int $durationNs
 * @property-read FieldTrace[]
 */
final class ExecutionTrace extends Holder
{
    public static function from(
        string $query,
        int    $startTime,
        int    $endTime,
        array  $fieldTraces,
    ): self
    {
        return new self([
            'query' => $query,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'fieldTraces' => $fieldTraces,
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