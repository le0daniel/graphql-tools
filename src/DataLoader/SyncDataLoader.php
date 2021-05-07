<?php

declare(strict_types=1);

namespace GraphQlTools\DataLoader;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoader;

abstract class SyncDataLoader implements DataLoader {

    private array $queued = [];
    private array|\ArrayAccess|null $loadedValues = null;

    abstract protected function load(array $identifiers): array|\ArrayAccess;
    abstract protected static function resolve(array|\ArrayAccess $loadedData, array $identifiers): null|array|\ArrayAccess;

    private function ensureLoaded(): null|array|\ArrayAccess {
        if (!isset($this->loadedValues)) {
            $this->loadedValues = $this->load($this->queued);
            $this->queued = [];
        }
        return $this->loadedValues;
    }

    public function loadSingleBy(string|int $identifier): SyncPromise {
        return $this->loadBy($identifier)
            ->then(fn(?array $data) => $data ? $data[0] ?? null : null);
    }

    public function loadBy(string|int ... $identifiers): SyncPromise {
        $this->loadedValues = null;
        array_push($this->queued, ...$identifiers);

        return (new SyncPromise(fn() => $this->ensureLoaded()))
            ->then(
                fn(array $data) => static::resolve($data, $identifiers)
            );
    }

}
