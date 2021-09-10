<?php

declare(strict_types=1);


namespace GraphQlTools\Immutable;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Utility\Time;

/**
 * Class Trace
 * @package GraphQlTools\Immutable
 *
 * @property-read int[]|string[] $path
 * @property-read string $parentType
 * @property-read string $returnType
 * @property-read string $fieldName
 * @property-read int $duration
 * @property-read array|null $exception
 * @property-read string $lastPathElement
 */
final class ResolverTrace extends Holder
{

    public static function fromSerialized(array $data): self
    {
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
        switch ($name) {
            case 'lastPathElement':
                $path = $this->path;
                return array_pop($path);
        }

        return parent::getValue($name);
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
