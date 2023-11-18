<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Data\ValueObjects\Events\EndEvent;
use GraphQlTools\Data\ValueObjects\Events\ParsedEvent;
use GraphQlTools\Data\ValueObjects\Events\StartEvent;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

abstract class Extension implements ExecutionExtension
{
    private const DEFAULT_PRIORITY = 100;
    protected string $name;

    public function getName(): string
    {
        return $this->name ?? static::class;
    }

    /**
     * Defines the priority of the extension. Lower numbers equals higher priority
     *
     * @return int
     */
    public function priority(): int
    {
        return self::DEFAULT_PRIORITY;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * React to Start Event, when GraphQL execution is started.
     *
     * @param StartEvent $event
     * @return void
     */
    public function start(StartEvent $event): void
    {

    }

    public function parsed(ParsedEvent $event): void
    {

    }

    /**
     * React to End Event, when GraphQL execution has been done.
     *
     * @param EndEvent $event
     * @return void
     */
    public function end(EndEvent $event): void
    {

    }

    public function visitField(VisitFieldEvent $event): ?Closure
    {
        return null;
    }
}
