<?php declare(strict_types=1);

namespace GraphQlTools\Test;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoader;
use GraphQlTools\Contract\ExecutableByDataLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DataLoaderTest extends TestCase
{

    private function createDataLoader(mixed $loadedData): DataLoader {
        return new DataLoader(new class ($loadedData) implements ExecutableByDataLoader {
            public function __construct(private mixed $loadedData)
            {
            }

            public function fetchData(array $queuedItems, array $arguments): mixed
            {
                return $this->loadedData;
            }
        });
    }

    private function resolvePromise(SyncPromise $promise): mixed {
        Deferred::runQueue();

        if ($promise->state !== SyncPromise::FULFILLED) {
            throw new RuntimeException("Expected promise to be fulfilled, got state '{$promise->state}'");
        }

        return $promise->result;
    }

    public function testLoad()
    {
        $firstId = 10;
        $dataLoader = $this->createDataLoader([$firstId => 'My String']);
        $result = $this->resolvePromise($dataLoader->loadAndMapManually($firstId)->then(fn($data) => $data[$firstId]));
        self::assertEquals('My String',$result);
        self::assertCount(1, $dataLoader->getLoadingTraces());

        self::assertEquals('My String', $this->resolvePromise($dataLoader->loadAndMapManually($firstId)->then(fn($data) => $data[$firstId])));
        self::assertCount(2, $dataLoader->getLoadingTraces());
    }
}
