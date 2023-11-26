<?php

declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;
use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Time;
use stdClass;

/**
 * @internal
 */
class OperationContext
{
    private ?array $result = null;
    private array $cache = [];
    private array $deferred = [];
    private int $currentRun = 0;
    private readonly stdClass $undefined;

    public function __construct(
        readonly public GraphQlContext $context,
        readonly public Extensions     $extensions,
        readonly public int            $maxRuns = 1,
    )
    {
        $this->undefined = new stdClass();
    }

    public function getContext(): GraphQlContext {
        return $this->context;
    }

    public function setResultData(?array $data): void
    {
        $this->result = $data;
    }

    private static function pathToString(array $path): string
    {
        return implode('.', $path);
    }

    public function deferField(array $path, ?string $label, mixed $typeData): void
    {
        $this->deferred[self::pathToString($path)] = [$path, $label, $typeData];
    }

    public function isDeferred(array $path): bool
    {
        return array_key_exists(self::pathToString($path), $this->deferred);
    }

    public function willResolveField(VisitFieldEvent $event): void {
        $this->extensions->willResolveField($event);
    }

    public function startRun(): void
    {
        $this->currentRun += 1;
    }

    public function endRun(): void {}

    public function isFirstRun(): bool
    {
        return $this->currentRun === 1;
    }

    public function canDefer(): bool {
        return $this->currentRun < $this->maxRuns;
    }

    public function shouldRunAgain(): bool
    {
        return $this->currentRun < $this->maxRuns && !empty($this->deferred);
    }

    public function getAllDeferred(): array
    {
        return array_map(
            fn(array $deferred): array => [$deferred[0], $deferred[1]],
            array_values($this->deferred)
        );
    }

    public function popDeferred(array $path): mixed
    {
        $path = self::pathToString($path);
        $typeData = $this->deferred[$path][2];
        unset($this->deferred[$path]);
        return $typeData;
    }

    /**
     * @param array $path
     * @return bool
     * @internal
     */
    public function isInResult(array $path): bool
    {
        if (null === $this->result || $this->isDeferred($path)) {
            return false;
        }

        return Arrays::getByPathArray($this->result, $path, $this->undefined) !== $this->undefined;
    }

    /**
     * @param string $path
     * @return mixed
     * @internal
     */
    public function getFromResult(array $path): mixed
    {
        return Arrays::getByPathArray($this->result, $path, null);
    }

    /**
     * Set an item into the cache
     * @param array $path
     * @param string $key
     * @param mixed $data
     * @return mixed
     */
    public function setCache(array $path, string $key, mixed $data): mixed
    {
        $this->cache[self::pathToString($path) . ":{$key}"] = $data;
        return $data;
    }

    /**
     * Get an item from the cache
     * @param array $path
     * @param string $key
     * @return mixed
     */
    public function getCache(array $path, string $key): mixed
    {
        return $this->cache[self::pathToString($path) . ":{$key}"] ?? null;
    }
}
