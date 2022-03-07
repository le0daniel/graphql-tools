<?php

declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQlTools\Events\FieldResolutionEvent;
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

    abstract public function jsonSerialize(): mixed;

    /**
     * Defines the priority of the extension. Lower numbers equals higher priority
     *
     * @return int
     */
    public function priority(): int {
        return self::DEFAULT_PRIORITY;
    }

    public function start(StartEvent $startEvent): void {

    }

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
     * @param FieldResolutionEvent $event
     * @return null|Closure(mixed $resolvedFieldValue) => $resolvedFieldValue
     */
    public function visitField(FieldResolutionEvent $event): ?Closure {
        return null;
    }
}
