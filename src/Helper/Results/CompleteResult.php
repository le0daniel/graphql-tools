<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Results;

use GraphQL\Error\DebugFlag;

final readonly class CompleteResult extends Result
{
    function appendToResult(array $result): array
    {
        return $result;
    }
}