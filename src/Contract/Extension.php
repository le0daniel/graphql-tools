<?php

declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQlTools\Events\VisitFieldEvent;
use GraphQlTools\Events\StartEvent;
use GraphQlTools\Events\EndEvent;
use JsonSerializable;

abstract class Extension implements JsonSerializable {

    private const DEFAULT_PRIORITY = 100;

    /**
     * Returns the key in the `extensions` property of the response
     *
     * @return string
     */
    abstract public function key(): string;

    /**
     * Serialize and expose the data to the extensions property of the result
     * using the key provided.
     *
     * @return mixed
     */
    abstract public function jsonSerialize(): mixed;

    /**
     * Determines if the extension is exposed to the client or not.
     *
     * @return bool
     */
    public function isVisibleInResult(): bool {
        return true;
    }

    /**
     * Defines the priority of the extension. Lower numbers equals higher priority
     *
     * @return int
     */
    public function priority(): int {
        return self::DEFAULT_PRIORITY;
    }

    /**
     * React to Start Event, when GraphQL execution is started.
     *
     * @param StartEvent $event
     * @return void
     */
    public function start(StartEvent $event): void {

    }

    /**
     * React to End Event, when GraphQL execution has been done.
     *
     * @param EndEvent $event
     * @return void
     */
    public function end(EndEvent $event): void {

    }

    /**
     * This is called before a field is resolved by the Proxy Resolver
     *
     * An optional returning Closure is called as soon as the field has finally been resolved.
     * Ex: return fn(mixed $resolvedFieldValue) => $resolvedFieldValue
     *
     * The callback gets the resolved value. The resolved value CAN NOT be modified.
     *
     * @param VisitFieldEvent $event
     * @return null|Closure(mixed $resolvedFieldValue) => $resolvedFieldValue
     */
    public function visitField(VisitFieldEvent $event): ?Closure {
        return null;
    }
}
