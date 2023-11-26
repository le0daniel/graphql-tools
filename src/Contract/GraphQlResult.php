<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Error\Error;

interface GraphQlResult
{

    /**
     * @return array<Error>
     */
    public function getErrors(): array;

    public function toArray(): array;

    public function getContext(): GraphQlContext;
}