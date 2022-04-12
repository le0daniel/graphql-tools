<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Utility\Time;
use RuntimeException;
use Throwable;

class DataLoader
{
    private const MAX_TRACES = 20;
    private array $loadingTraces = [];
    private array $queuedItems = [];
    private mixed $loadedData = null;

    final public function __construct(protected ExecutableByDataLoader $callable, private array $arguments = [])
    {
    }

    private function traceDataLoading(int $duration)
    {
        if (count($this->loadingTraces) >= self::MAX_TRACES) {
            array_shift($this->loadingTraces);
        }
        $this->loadingTraces[] = [
            'durationInNanoSeconds' => $duration
        ];
    }

    protected function loadData(array $queuedItems): mixed
    {
        return $this->callable->fetchData($queuedItems, $this->arguments);
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

    final public function clearQueueIfNeeded(): void
    {
        if ($this->loadedData) {
            $this->loadedData = null;
            $this->queuedItems = [];
        }
    }

    final public function getLoadingTraces(): array
    {
        return $this->loadingTraces;
    }

    final public function load(mixed $id): SyncPromise {
        return $this->loadAndMapManually($id)
            ->then(static fn($loadedData) => $loadedData[$id] ?? null);
    }

    final public function loadMany(mixed ...$ids): SyncPromise {
        return $this->loadAndMapManually(...$ids)->then(static function ($loadedData) use ($ids) {
            $items = [];
            foreach ($ids as $id) {
                $items[] = $loadedData[$id] ?? null;
            }
            return $items;
        });
    }

    final public function loadAndMapManually(mixed...$items): SyncPromise
    {
        $this->clearQueueIfNeeded();
        array_push($this->queuedItems, ...$items);
        return new Deferred(function () {
            $this->loadDataOnce();
            $this->throwOnDataLoadingError();
            return $this->loadedData;
        });
    }
}