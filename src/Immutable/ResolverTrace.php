<?php

declare(strict_types=1);


namespace GraphQlTools\Immutable;


use GraphQL\Type\Definition\ResolveInfo;
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
        if (!Arrays::keysExist($data, self::REQUIRED_ARRAY_KEYS)) {
            $gottenArrayKeys = implode(', ', array_keys($data));
            $expectedArrayKeys = implode(', ', self::REQUIRED_ARRAY_KEYS);
            throw new RuntimeException("Not all required keys were set. Got: {$gottenArrayKeys}. Expected: {$expectedArrayKeys}");
        }

        return new self([
            'path' => $data['path'],
            'parentType' => $data['parentType'],
            'fieldName' => $data['fieldName'],
            'returnType' => $data['returnType'],
            'duration' => $data['duration'],
            'startOffset' => $data['startOffset'],
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

    public static function fromResolveInfo(ResolveInfo $info, int $preciseResolveStart, int $preciseExecutionStart): ResolverTrace
    {
        $endTimeInNanoseconds = Time::nanoSeconds();
        $durationInNanoseconds = $endTimeInNanoseconds - $preciseResolveStart;

        return new self(
            [
                'path' => $info->path,
                'parentType' => $info->parentType->name,
                'fieldName' => $info->fieldName,
                'returnType' => (string)$info->returnType,
                'duration' => $durationInNanoseconds,
                'startOffset' => $preciseResolveStart - $preciseExecutionStart,
            ]
        );
    }


}
