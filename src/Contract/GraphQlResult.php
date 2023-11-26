<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;

interface GraphQlResult
{

    /**
     * @return array<Error>
     */
    public function getErrors(): array;

    public function toArray(int $debug = DebugFlag::NONE): array;

    public function getContext(): GraphQlContext;
}