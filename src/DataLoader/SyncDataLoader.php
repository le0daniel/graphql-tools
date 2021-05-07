<?php

declare(strict_types=1);

namespace GraphQlTools\DataLoader;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Utility\Time;

abstract class SyncDataLoader implements DataLoader {

    /**
     * Array containing the queued Identifiers for loading
     *
     * @var array
     */
    private array $queued = [];

    /**
     * Loaded data
     *
     * @var array|\ArrayAccess|null
     */
    private array|\ArrayAccess|null $loadedValues = null;

    /**
     * Durations of all the individual loads triggered.
     *
     * @var array
     */
    private array $loadingDurations = [];

    /**
     * Load the required data given an array of Identifiers.
     * This method should not return NULL.
     *
     * @param array $identifiers
     * @return mixed
     */
    abstract protected function load(array $identifiers): mixed;

    /**
     * Resolve the specific identifiers given the loaded data.
     *
     * @param mixed $loadedData
     * @param array $identifiers
     * @return mixed
     */
    abstract protected static function resolve(mixed $loadedData, array $identifiers): mixed;

    /**
     * Returns the duration of all the triggered loading.
     * @return array
     */
    final public function getLoadingDurations(): array {
        return $this->loadingDurations;
    }

    /**
     * Returns queued identifiers and resets the state.
     * @return array
     */
    private function popQueuedIdentifiers(): array {
        $queuedIdentifiers = $this->queued;
        $this->queued = [];
        return $queuedIdentifiers;
    }

    /**
     * Verifies some data has been loaded. The load method should NOT return NULL.
     */
    private function verifyLoadedValuesAreNotNull(): void {
        if ($this->loadedValues === null) {
            throw new \RuntimeException("DataLoader unexpectedly returned NULL. This should not happen.");
        }
    }

    /**
     * Ensures that the queued IDs are loaded before the final resolution
     *
     * @return mixed
     */
    private function ensureLoadedAndReturnValues(): mixed {
        if (empty($this->queued)) {
            return null;
        }

        if (!isset($this->loadedValues)) {
            $startTime = Time::nanoSeconds();
            $this->loadedValues = $this->load($this->popQueuedIdentifiers());
            $this->verifyLoadedValuesAreNotNull();
            $this->loadingDurations[] = Time::nanoSeconds() - $startTime;
        }

        return $this->loadedValues;
    }

    /**
     * Helper to only get a single value out of the data loader.
     *
     * @param string|int $identifier
     * @return SyncPromise
     */
    public function loadSingleBy(string|int $identifier): SyncPromise {
        return $this->loadBy($identifier)
            ->then(fn(mixed $data) => $data ? $data[0] ?? null : null);
    }

    public function loadBy(string|int ... $identifiers): SyncPromise {
        $this->loadedValues = null;
        array_push($this->queued, ...$identifiers);

        return (new SyncPromise(fn() => $this->ensureLoadedAndReturnValues()))
            ->then(
                fn(mixed $data) => static::resolve($data, $identifiers)
            );
    }

}
