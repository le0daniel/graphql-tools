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
    public const IDENTIFIER_KEY = 'itemId';
    private array $loadingTraces = [];
    private array $queuedItems = [];
    private mixed $loadedData = null;

    final public function __construct(protected readonly Closure|ExecutableByDataLoader $callable)
    {
    }

    public function getLoadingTraces(): array
    {
        return $this->loadingTraces;
    }

    /**
     * Defer the loading of an Item. An item *MUST* be an identifier (string|int), object or an array with
     * a property called 'id' for identification.
     *
     * To defer loading of items containing arguments, you can use something similar to:
     * `$dataLoader->load(['id' => 1, 'args' => [...]])`
     * Then make sure that the data loader returns a data structure where the value can be accessed as
     * $data[$item['id']]. In case you enqueue an object, return an SqlObjectStorage instance.
     *
     * @param mixed $item
     * @return SyncPromise
     */
    public function load(mixed $item): SyncPromise
    {
        $this->clearLoadedDataIfNeeded();
        $this->verifyArrayItemsContainIdentifier($item);
        $this->queuedItems[] = &$item;

        // If an array is given, an identifier is required to map to the correct data. This is due
        // to arrays being passed as values and not as references.
        $identifier = is_array($item) ? $item[self::IDENTIFIER_KEY] : $item;

        return new Deferred(function () use (&$identifier) {
            $this->loadDataOnce();
            $this->throwOnDataLoadingError();

            $valueOrThrowable = $this->loadedData[$identifier] ?? null;

            // For a throwable which is an instance
            if ($valueOrThrowable instanceof Throwable) {
                throw $valueOrThrowable;
            }
            return $valueOrThrowable;
        });
    }

    public function loadMany(mixed ...$items): SyncPromise
    {
        $promises = array_map(fn(mixed $item): SyncPromise => $this->load($item), $items);
        return new SyncPromise(static fn() => $promises);
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
        if ($this->loadedData === null) {
            throw new RuntimeException("Loaded data is unexpectedly NULL.");
        }

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
            $this->loadedData = $this->loadData($this->unqueueItems());
            if ($this->loadedData === null) {
                throw new RuntimeException(
                    "DataLoader failed to load data, null is not an acceptable value, got: 'null'." . PHP_EOL .
                    "Hint: Ensure that the loading function always returns a value which is not null, throw an error instead."
                );
            }
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
        if (is_array($item) && !array_key_exists(self::IDENTIFIER_KEY, $item)) {
            $keyName = 'DataLoader::IDENTIFIER_KEY';
            throw new RuntimeException(
                "An item enqueued in a dataloader is required to have a property called '{$keyName}' for mapping." . PHP_EOL .
                "Hint: Make sure to use \$dataLoader->load([{$keyName} => 'yourID!', ...])."
            );
        }
    }
}