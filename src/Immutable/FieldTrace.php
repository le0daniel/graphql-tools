<?php

declare(strict_types=1);


namespace GraphQlTools\Immutable;


use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Utility\Time;

/**
 * Class Trace
 * @package GraphQlTools\Immutable
 *
 * @property-read array $path
 * @property-read string $parentType
 * @property-read string $returnType
 * @property-read string $fieldName
 * @property-read int $duration
 * @property-read array|null $exception
 */
final class FieldTrace extends Holder
{

    public static function fromResolveInfo(ResolveInfo $info, int $preciseResolveStart, int $preciseExecutionStart): FieldTrace
    {
        $endTimeInNanoseconds = Time::nanoSeconds();
        $durationInNanoseconds = $endTimeInNanoseconds - $preciseResolveStart;

        return new self(
            [
                'path' => $info->path,
                'parentType' => $info->parentType->name,
                'fieldName' => $info->fieldName,
                'returnType' => (string) $info->returnType,
                'duration' => $durationInNanoseconds,
                'startOffset' => $preciseResolveStart - $preciseExecutionStart,
            ]
        );
    }


}
