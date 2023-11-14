<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\ParsedEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;

/**
 * @template T as GraphQlContext
 */
interface ExecutionExtension
{
    public function priority(): int;

    public function start(StartEvent $event): void;

    public function end(EndEvent $event): void;

    public function parsed(ParsedEvent $event): void;

    public function visitField(VisitFieldEvent $event): ?Closure;

    public function getName(): string;
}