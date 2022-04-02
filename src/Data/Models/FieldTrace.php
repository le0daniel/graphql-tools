<?php

declare(strict_types=1);


namespace GraphQlTools\Data\Models;


use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Time;

/**
 * @property-read string[]|int[] $path
 * @property-read string $parentType
 * @property-read string $fieldName
 * @property-read string $returnType
 * @property-read int $duration
 * @property-read int $startOffset
 * @property-read string $lastPathElement
 * @property-read string $pathKey
 */
final class FieldTrace extends Holder
{
    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'lastPathElement' => Arrays::last($this->path),
            'pathKey' => implode('.', $this->path),
            default => parent::getValue($name),
        };
    }

    public static function fromEvent(VisitFieldEvent $event, int $preciseExecutionStart): FieldTrace
    {
        $endTimeInNanoseconds = Time::nanoSeconds();
        $durationInNanoseconds = $endTimeInNanoseconds - $event->eventTimeInNanoSeconds;

        return new self(
            [
                'path' => $event->info->path,
                'parentType' => $event->info->parentType->name,
                'fieldName' => $event->info->fieldName,
                'returnType' => (string)$event->info->returnType,
                'duration' => $durationInNanoseconds,
                'startOffset' => $event->eventTimeInNanoSeconds - $preciseExecutionStart,
            ]
        );
    }


}
