<?php declare(strict_types=1);

namespace GraphQlTools\Helper;

use Closure;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoaderIdentifiable;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Utility\Time;
use RuntimeException;
use Throwable;
use GraphQlTools\Contract\DataLoader as DataLoaderContract;

/**
 * @implements DataLoaderContract<SyncPromise>
 */
final class DataLoader implements DataLoaderContract
{
    public const IDENTIFIER_KEY = 'itemId';
    private array $loadingTraces = [];
    private array $queuedItems = [];
    private mixed $loadedData = null;

    /**
     * Provide an Executor to the DataLoader. An Executor must accept an array of items (mixed)
     * and returned a Dictionary (key => value) which is assignable to the loaded items.
     *
     * In case you expect the items to be an object, use
     *
     * @param Closure|ExecutableByDataLoader $loader
     */
    final public function __construct(protected readonly Closure|ExecutableByDataLoader $loader)
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
     * `$dataLoader->load(['itemId' => 1, 'args' => [...]])`
     * Then make sure that the data loader returns a data structure where the value can be accessed as
     * $data[$item['id']]. In case you enqueue an object, return an SqlObjectStorage instance.
     *
     * @param mixed $item
     * @return SyncPromise
     */
    public function load(mixed $item): SyncPromise
    {
        $this->clearLoadedDataIfNeeded();
        $this->queuedItems[] = &$item;

        // If an array is given, an identifier is required to map to the correct data. This is due
        // to arrays being passed as values and not as references.
        $identifier = $this->identifier($item);

        return new Deferred(function () use (&$identifier) {
            $this->loadDataOnce();
            $this->throwOnDataLoadingError();

            $valueOrThrowable = $this->loadedData[$identifier] ?? null;

            if ($valueOrThrowable instanceof Throwable) {
                // This will reject the promise
                throw $valueOrThrowable;
            }
            return $valueOrThrowable;
        });
    }

    private function identifier(mixed &$item): mixed {
        if (is_array($item)) {
            $this->verifyArrayItemsContainIdentifier($item);
            return $item[self::IDENTIFIER_KEY];
        }

        return $item instanceof DataLoaderIdentifiable
            ? $item->dataLoaderIdentifier()
            : $item;
    }

    public function loadMany(mixed ...$items): array
    {
        return array_map(fn(mixed $item): SyncPromise => $this->load($item), $items);
    }

    private function traceDataLoading(int $duration): void
    {
        $this->loadingTraces[] = [
            'durationInNanoSeconds' => $duration
        ];
    }

    private function loadData(array $queuedItems): mixed
    {
        return $this->loader instanceof ExecutableByDataLoader
            ? $this->loader->fetchData($queuedItems)
            : ($this->loader)($queuedItems);
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

    private function verifyArrayItemsContainIdentifier(array &$item): void
    {
        if (!array_key_exists(self::IDENTIFIER_KEY, $item)) {
            $keyName = 'DataLoader::IDENTIFIER_KEY';
            throw new RuntimeException(
                "An item enqueued in a dataloader is required to have a property called '{$keyName}' for mapping." . PHP_EOL .
                "Hint: Make sure to use \$dataLoader->load([{$keyName} => 'yourID!', ...])."
            );
        }
    }
}