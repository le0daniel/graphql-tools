<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

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