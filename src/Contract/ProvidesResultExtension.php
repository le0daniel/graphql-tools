<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use JsonSerializable;

interface ProvidesResultExtension extends JsonSerializable
{
    public function isVisibleInResult($context): bool;
    public function key(): string;
}