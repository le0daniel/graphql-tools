<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;
use JsonSerializable;

interface ExecutionExtension extends JsonSerializable
{
    public function key(): string;

    public function isVisibleInResult(): bool;

    public function priority(): int;

    public function start(StartEvent $event): void;

    public function end(EndEvent $event): void;

    public function visitField(VisitFieldEvent $event): ?Closure;
}