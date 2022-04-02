<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

/**
 * @property-read string $query
 * @property-read int $startTime
 * @property-read int $endTime
 * @property-read int $durationNs
 * @property-read FieldTrace[]
 * @property-read GraphQlError[] $errors
 */
final class ExecutionTrace extends Holder
{
    public static function from(
        string $query,
        int    $startTime,
        int    $endTime,
        array  $fieldTraces,
        array $errors,
    ): self
    {
        Holder::verifyListOfInstances(FieldTrace::class, $fieldTraces);
        Holder::verifyListOfInstances(GraphQlError::class, $errors);

        return new self([
            'query' => $query,
            'startTime' => $startTime,
            'endTime' => $endTime,
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