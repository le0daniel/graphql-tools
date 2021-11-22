<?php declare(strict_types=1);

namespace GraphQlTools\Immutable;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQlTools\Utility\QuerySignature;

/**
 * @property-read int $version
 * @property-read string $query
 * @property-read string $queryId
 * @property-read DateTimeImmutable $startTime
 * @property-read DateTimeImmutable $endTime
 * @property-read int $durationNs
 * @property-read int $duration
 * @property-read array $execution
 * @property-read ResolverTrace[] $executionResolvers
 */
final class ExecutionTrace extends Holder
{
    public static function from(
        int               $version,
        string            $query,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        int               $durationNs,
        array             $executionResolvers
    ): self
    {
        return new self([
            'version' => $version,
            'query' => $query,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'durationNs' => $durationNs,
            'execution' => [
                'resolvers' => $executionResolvers
            ]
        ]);
    }

    private static function dateToString(DateTimeImmutable $dateTime): string {
        return $dateTime->format(DateTimeInterface::RFC3339_EXTENDED);
    }

    private static function dateFromString(string $dateTime): DateTimeImmutable {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $dateTime);
    }

    public static function fromSerialized(array $data): self
    {
        return new self([
            'version' => $data['version'],
            'query' => $data['query'],
            'startTime' => self::dateFromString($data['startTime']),
            'endTime' => self::dateFromString($data['endTime']),
            'durationNs' => $data['durationNs'],
            'execution' => [
                'resolvers' => array_map([ResolverTrace::class, 'fromSerialized'], $data['execution']['resolvers'])
            ]
        ]);
    }

    protected function getValueForSerialization(string $name): mixed
    {
        $value = parent::getValueForSerialization($name);
        return match ($name) {
            'startTime', 'endTime' => self::dateToString($value),
            default => $value,
        };
    }

    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'duration' => $this->durationNs,
            'executionResolvers' => $this->execution['resolvers'],
            'queryId' => QuerySignature::createSignatureString($this->query),
            default => parent::getValue($name),
        };
    }

}