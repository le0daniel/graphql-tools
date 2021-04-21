<?php

declare(strict_types=1);

namespace GraphQlTools\Contract;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;

abstract class Extension implements \JsonSerializable {

    private const DEFAULT_PRIORITY = 100;

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

    /**
     * called when the query execution is started.
     * This happens before parsing
     *
     * @param int $eventTimeInNanoseconds
     * @param string $query
     */
    public function start(int $eventTimeInNanoseconds, string $query): void {

    }

    /**
     * Called when the operation was successfully executed.
     *
     * @param int $eventTimeInNanoseconds
     */
    public function end(int $eventTimeInNanoseconds): void {

    }

    /**
     * This is called before a field is resolved by the Proxy Resolver
     *
     * An optional returning Closure is called as soon as the field has finally been resolved.
     * Ex: return fn(mixed $value) => void
     *
     * @param int $eventTimeInNanoseconds
     * @param $typeData
     * @param array $arguments
     * @param ResolveInfo $info
     * @return Closure(mixed $value): void|null
     */
    public function fieldResolution(int $eventTimeInNanoseconds, $typeData, array $arguments, ResolveInfo $info): ?Closure {
        return null;
    }
}
