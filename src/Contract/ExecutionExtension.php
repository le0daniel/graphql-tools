<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

/**
 * @template T as GraphQlContext
 */
interface ExecutionExtension
{
    public function priority(): int;

    public function getName(): string;

    public function isEnabled(): bool;
}