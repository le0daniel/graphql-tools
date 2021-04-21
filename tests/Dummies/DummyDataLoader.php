<?php

declare(strict_types=1);

namespace GraphQlTools\Test\Dummies;

use GraphQlTools\DataLoader\SyncDataLoader;

final class DummyDataLoader extends SyncDataLoader {

    private int $loadCount = 0;
    private array $loadedIds = [];

    public function getLoadCount(): int{
        return $this->loadCount;
    }

    public function getLoadedIds(): array{
        return $this->loadedIds;
    }

    protected function load(array $identifiers): array{
        $this->loadCount++;
        $this->loadedIds = array_unique($identifiers);

        $data = [];
        foreach ($identifiers as $identifier) {
            $data[$identifier] = ['id' => $identifier, 'data' => 'DATA'];
        }
        return $data;
    }

    protected static function resolve(array $loadedData, array $identifiers): ?array{
        $resolvedData = [];
        foreach ($identifiers as $id) {
            $resolvedData[] = $loadedData[$id];
        }
        return $resolvedData;
    }
}
