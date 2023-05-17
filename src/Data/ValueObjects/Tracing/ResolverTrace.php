<?php

declare(strict_types=1);


namespace GraphQlTools\Data\ValueObjects\Tracing;


use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Time;

final class ResolverTrace
{
    /**
     * @param array<int|string> $path
     * @param string $parentType
     * @param string $fieldName
     * @param string $returnType
     * @param int $duration
     * @param int $startOffset
     */
    public function __construct(
        public readonly array  $path,
        public readonly string $parentType,
        public readonly string $fieldName,
        public readonly string $returnType,
        public readonly int    $duration,
        public readonly int    $startOffset,
    )
    {
    }

    public function lastPathElement(): string|int
    {
        return Arrays::last($this->path);
    }

    public function pathKey(): string
    {
        return implode('.', $this->path);
    }

    public static function fromEvent(VisitFieldEvent $event, int $preciseExecutionStart): ResolverTrace
    {
        $endTimeInNanoseconds = Time::nanoSeconds();
        $durationInNanoseconds = $endTimeInNanoseconds - $event->eventTimeInNanoSeconds;

        return new self(
            $event->info->path,
            $event->info->parentType->name,
            $event->info->fieldName,
            (string)$event->info->returnType,
            $durationInNanoseconds,
            $event->eventTimeInNanoSeconds - $preciseExecutionStart,
        );
    }


}
