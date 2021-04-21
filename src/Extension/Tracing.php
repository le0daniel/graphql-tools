<?php

declare(strict_types=1);

namespace GraphQlTools\Extension;

use DateTimeImmutable;
use DateTimeInterface;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Contract\Extension;
use GraphQlTools\Immutable\FieldTrace;
use Closure;

final class Tracing extends Extension {

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
     * @var FieldTrace[]
     */
    private array $fieldTraces = [];

    /**
     * Defines the priority of this extension.
     *
     * As this extension requires a high priority and should run first to capture the duration
     * of resolvers correctly, it is set to -1
     *
     * @return int
     */
    public function priority(): int{
        return -1;
    }

    /**
     * Defines the key of the extension in the extension result array.
     *
     * @return string
     */
    public function key(): string{
        return 'tracing';
    }

    /**
     * Serialize the content of the extension after successfully running the query.
     *
     * @return array
     */
    public function jsonSerialize(): array{
        return [
            'version' => self::VERSION,
            'startTime' => $this->startTime->format(DateTimeInterface::RFC3339_EXTENDED),
            'endTime' => $this->endTime->format(DateTimeInterface::RFC3339_EXTENDED),
            'duration' => $this->endTimeInNanoseconds - $this->startTimeInNanoseconds,
            'execution' => [
                'resolvers' => $this->fieldTraces
            ]
        ];
    }

    public function start(int $eventTimeInNanoseconds, string $query): void{
        $this->startTime = new DateTimeImmutable();
        $this->startTimeInNanoseconds = $eventTimeInNanoseconds;
    }

    public function end(int $eventTimeInNanoseconds): void{
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
    public function fieldResolution(int $eventTimeInNanoseconds, $typeData, array $arguments, ResolveInfo $info): Closure {
        return function($value) use ($eventTimeInNanoseconds, $info): void {

            // Add trace as soon as the field is resolved
            $this->fieldTraces[] = FieldTrace::fromResolveInfo(
                $info,
                $eventTimeInNanoseconds,
                $this->startTimeInNanoseconds
            );
        };
    }
}
