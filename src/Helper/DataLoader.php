<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Utility\Time;
use RuntimeException;
use Throwable;

final class DataLoader
{
    private const IDENTIFIER_ARRAY_KEY = 'id';
    private array $loadingTraces = [];
    private array $queuedItems = [];
    private mixed $loadedData = null;

    /**
     * @param ExecutableByDataLoader|callable $callable
     * @param array $arguments
     */
    final public function __construct(protected readonly Closure|ExecutableByDataLoader $callable)
    {
    }

    private function traceDataLoading(int $duration): void
    {
        $this->loadingTraces[] = [
            'durationInNanoSeconds' => $duration
        ];
    }

    private function loadData(array $queuedItems): mixed
    {
        return $this->callable instanceof ExecutableByDataLoader
            ? $this->callable->fetchData($queuedItems)
            : ($this->callable)($queuedItems);
    }

    private function unqueueItems(): array
    {
        $queuedItems = $this->queuedItems;
        $this->queuedItems = [];
        return $queuedItems;
    }

    private function throwOnDataLoadingError(): void
    {
        if ($this->loadedData instanceof Throwable) {
            throw $this->loadedData;
        }
    }

    private function loadDataOnce(): void
    {
        if ($this->loadedData !== null) {
            return;
        }

        $startTime = Time::nanoSeconds();

        try {
            $result = $this->loadData($this->unqueueItems());
            if ($result === null) {
                throw new RuntimeException("DataLoader failed to load data. Expected not null, got null.");
            }
            $this->loadedData = $result;
        } catch (Throwable $exception) {
            $this->loadedData = $exception;
        } finally {
            $this->traceDataLoading(Time::nanoSeconds() - $startTime);
        }
    }

    private function clearLoadedDataIfNeeded(): void
    {
        if ($this->loadedData) {
            $this->loadedData = null;
            $this->queuedItems = [];
        }
    }

    private function verifyArrayItemsContainIdentifier(mixed &$item): void {
        if (is_array($item) && !array_key_exists(self::IDENTIFIER_ARRAY_KEY, $item)) {
            $keyName = self::IDENTIFIER_ARRAY_KEY;
            throw new RuntimeException(
                "An item enqueued in a dataloader is required to have a property called '{$keyName}' for mapping." . PHP_EOL .
                "Hint: Make sure to use \$dataLoader->load(['id' => 'yourID!', ...])."
            );
        }
    }

    public function getLoadingTraces(): array
    {
        return $this->loadingTraces;
    }

    final public function load(mixed $item): SyncPromise
    {
        $this->clearLoadedDataIfNeeded();
        $this->verifyArrayItemsContainIdentifier($item);
        $this->queuedItems[] = &$item;

        // If an array is given, an identifier is required to map to the correct data. This is due
        // to arrays being passed as values and not as references.
        $identifier = is_array($item) ? $item[self::IDENTIFIER_ARRAY_KEY] : $item;

        return new Deferred(function () use (&$identifier) {
            $this->loadDataOnce();
            $this->throwOnDataLoadingError();

            $valueOrThrowable = $this->loadedData[$identifier] ?? null;
            if ($valueOrThrowable instanceof Throwable) {
                throw $valueOrThrowable;
            }
            return $valueOrThrowable;
        });
    }

    final public function loadMany(mixed ...$items): SyncPromise
    {
        $promises = array_map(fn(mixed $item): SyncPromise => $this->load($item), $items);
        return new SyncPromise(static fn() => $promises);
    }
}