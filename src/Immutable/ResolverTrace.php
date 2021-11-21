<?php

declare(strict_types=1);


namespace GraphQlTools\Immutable;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Events\FieldResolutionEvent;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Time;
use RuntimeException;

/**
 * Class Trace
 * @package GraphQlTools\Immutable
 *
 * @property-read string[]|int[] $path
 * @property-read string $parentType
 * @property-read string $fieldName
 * @property-read string $returnType
 * @property-read int $duration
 * @property-read int $startOffset
 * @property-read string $lastPathElement
 * @property-read string $pathKey
 */
final class ResolverTrace extends Holder
{
    private const REQUIRED_ARRAY_KEYS = [
        'path',
        'parentType',
        'fieldName',
        'returnType',
        'duration',
        'startOffset',
    ];

    public static function fromSerialized(array $data): self
    {
        $verifiedData = Arrays::onlyKeys($data, self::REQUIRED_ARRAY_KEYS);

        return new self([
            'path' => $verifiedData['path'],
            'parentType' => $verifiedData['parentType'],
            'fieldName' => $verifiedData['fieldName'],
            'returnType' => $verifiedData['returnType'],
            'duration' => $verifiedData['duration'],
            'startOffset' => $verifiedData['startOffset'],
        ]);
    }

    protected function getValue(string $name): mixed
    {
        return match ($name) {
            'lastPathElement' => Arrays::last($this->path),
            'pathKey' => implode('.', $this->path),
            default => parent::getValue($name),
        };
    }

    public static function fromEvent(FieldResolutionEvent $event, int $preciseExecutionStart): ResolverTrace
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
