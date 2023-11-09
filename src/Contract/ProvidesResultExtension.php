<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Error\DebugFlag;

interface ProvidesResultExtension
{
    public function isVisibleInResult($context): bool;
    public function key(): string;
    public function serialize(int $debug = DebugFlag::NONE): mixed;
}