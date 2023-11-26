<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Time;
use RuntimeException;
use stdClass;

final class ExecutionManager
{
    private int $currentExecution = 0;
    private int $startTimeNs;
    private array $deferred = [];
    private array $cache = [];
    private null|array $result = null;
    private stdClass $undefined;

    public function __construct(
        private readonly int $maxExecutions = 10,
    )
    {
        $this->undefined = new stdClass();
    }

    public function start(): void
    {
        if (isset($this->startTimeNs)) {
            throw new RuntimeException("Is already running.");
        }

        $this->currentExecution++;
        $this->startTimeNs = Time::nanoSeconds();
    }

    public function stop(): int
    {
        if (!isset($this->startTimeNs)) {
            throw new RuntimeException("Can not stop when not running.");
        }

        $startTime = $this->startTimeNs;
        unset($this->startTimeNs);
        return Time::nanoSeconds() - $startTime;
    }

    public function getCurrentExecution(): int
    {
        return $this->currentExecution;
    }

    public function addDefer(array $path, ?string $label, mixed $data): void
    {
        if (!$this->canExecuteAgain()) {
            throw new RuntimeException("You can not run again, so deferring further is not allowed.");
        }

        $this->deferred[self::pathToString($path)] = [$path, $label, $data];
    }

    public function isDeferred(array $path): bool
    {
        return array_key_exists(self::pathToString($path), $this->deferred);
    }

    /**
     * Tells us if it is possible to defer if a field requires this.
     * @return bool
     */
    public function canExecuteAgain(): bool
    {
        return $this->currentExecution < $this->maxExecutions;
    }

    public function hasDeferred(): bool
    {
        return !empty($this->deferred);
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
        $data = $this->deferred[$path][2];
        unset($this->deferred[$path]);
        return $data;
    }

    public function setResult(?array $result): void
    {
        if (isset($this->startTimeNs)) {
            throw new RuntimeException("Not allowed to manipulate result during execution");
        }

        $this->result = $result;
    }

    /**
     * @param array $path
     * @return bool
     * @internal
     */
    public function isInResult(array $path): bool
    {
        // If the path was deferred, this means the previous result should show null.
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

    private static function pathToString(array $path): string
    {
        return implode('.', $path);
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