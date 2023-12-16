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

    public function __construct(
        private readonly int $maxExecutions = 10,
    )
    {
        if ($this->maxExecutions < 1) {
            throw new RuntimeException("Max runs needs to be bigger than 1, got: {$this->maxExecutions}");
        }
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

    /**
     * Returns if an item at a specific path has been deferred.
     * @param array $path
     * @return bool
     */
    public function isDeferred(array $path): bool
    {
        return array_key_exists(self::pathToString($path), $this->deferred);
    }

    /**
     * Tells us if it is possible to defer if a field requires this.
     * It checks for the max runs allowed and if a next run is allowed.
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

    private static function pathToString(array $path): string
    {
        return implode('.', $path);
    }
}