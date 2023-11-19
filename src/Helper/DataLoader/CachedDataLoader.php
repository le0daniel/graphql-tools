<?php declare(strict_types=1);

namespace GraphQlTools\Helper\DataLoader;

use Closure;
use GraphQL\Deferred;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Contract\DataLoaderIdentifiable;
use GraphQlTools\Contract\ExecutableByDataLoader;
use RuntimeException;
use Throwable;

class CachedDataLoader implements DataLoader
{
    private array $loadedItems = [];
    private array $queuedItems = [];

    public function __construct(private readonly ExecutableByDataLoader|Closure $loader)
    {
    }

    public function load(mixed $item): mixed
    {
        $identifier = self::getIdentifier($item);
        if (array_key_exists($identifier, $this->loadedItems)) {
            return $this->loadedItems[$identifier];
        }

        $this->queuedItems[] = $identifier;
        return new Deferred(function () use (&$identifier) {
            $this->loadQueuedItems();
            $value = $this->loadedItems[$identifier] ?? null;
            if ($value instanceof Throwable) {
                throw $value;
            }
            return $value;
        });
    }

    public function loadMany(...$items): array
    {
        return array_map(fn(mixed $item): mixed => $this->load($item), $items);
    }

    private function loadQueuedItems(): void
    {
        if (empty($this->queuedItems)) {
            return;
        }

        $itemsToLoad = $this->dequeueItems();

        try {
            $data = $this->loader instanceof ExecutableByDataLoader
                ? $this->loader->fetchData($itemsToLoad)
                : ($this->loader)($itemsToLoad);
        } catch (Throwable $exception) {
            // In case of failure, all items are assigned the throwable.
            foreach ($itemsToLoad as $identifier) {
                $this->loadedItems[$identifier] = $exception;
            }
            return;
        }

        foreach ($itemsToLoad as $key) {
            $this->loadedItems[$key] = $data[$key] ?? null;
        }
    }

    public function clear(): void
    {
        $this->loadedItems = [];
    }

    private function dequeueItems(): array
    {
        $queuedItems = $this->queuedItems;
        $this->queuedItems = [];
        return $queuedItems;
    }

    private static function getIdentifier(mixed $item): string|int
    {
        return match (true) {
            is_string($item), is_int($item) => $item,
            $item instanceof DataLoaderIdentifiable => $item->dataLoaderIdentifier(),
            default => throw new RuntimeException("Expected item to be string|int|instance of DataLoaderIdentifiable")
        };
    }
}