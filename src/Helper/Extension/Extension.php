<?php

declare(strict_types=1);

namespace GraphQlTools\Helper\Extension;

use Closure;
use GraphQlTools\Contract\ExecutionExtension;
use GraphQlTools\Contract\ProvidesResultExtension;
use GraphQlTools\Events\EndEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\VisitFieldEvent;

abstract class Extension implements ExecutionExtension, ProvidesResultExtension
{
    private const DEFAULT_PRIORITY = 100;
    protected string $name;

    /**
     * Determines if the extension is exposed to the client or not.
     *
     * @param mixed $context
     * @return bool
     */
    public function isVisibleInResult(mixed $context): bool
    {
        return true;
    }

    public function getName(): string {
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

    public function isEnabled(): bool {
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
