<?php declare(strict_types=1);

namespace GraphQlTools\Contract\Extension;

interface InteractsWithMultipleRuns
{
    public function setDepth(int $level): void;
    public function hasNext(): bool;

}