<?php declare(strict_types=1);

namespace GraphQlTools\Test\Contract;

use Closure;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Helper\DataLoader;
use PHPUnit\Framework\TestCase;

class DataLoaderTest extends TestCase
{
    private function getDataLoader(array $data, ?Closure $dataAssertions = null)
    {
        $counter = new class () {
            public int $count = 0;

            public function increase(): void
            {
                $this->count++;
            }
        };

        return [
            $counter,
            new DataLoader(function (...$args) use ($counter, $data, $dataAssertions) {
                if ($dataAssertions) {
                    $dataAssertions(...$args);
                }
                $counter->increase();
                return $data;
            })
        ];
    }

    private static function promissesToValues(array $promisses): array {
        return array_map(static fn(SyncPromise $promise) => $promise->result, $promisses);
    }

    public function testMultipleLoads()
    {
        $dataLoader = new DataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promise1 = $dataLoader->load(2);
        Deferred::runQueue();

        $promise2 = $dataLoader->load(2);
        Deferred::runQueue();

        self::assertEquals('other', $promise1->result);
        self::assertEquals('other', $promise2->result);
    }

    public function testLoadingWithArrayItems()
    {
        $dataLoader = new DataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promise1 = $dataLoader->load(['id' => 1, 'args' => []]);
        $promise2 = $dataLoader->load(['id' => 2, 'args' => []]);
        Deferred::runQueue();

        self::assertEquals('test', $promise1->result);
        self::assertEquals('other', $promise2->result);
    }

    public function testLoadMany()
    {
        $dataLoader = new DataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promise1 = $dataLoader->loadMany(1, 2);
        $promise2 = $dataLoader->loadMany(1, 3);
        Deferred::runQueue();

        self::assertEquals('test', $promise1->result[0]->result);
        self::assertEquals('other', $promise1->result[1]->result);

        self::assertEquals('test', $promise2->result[0]->result);
        self::assertEquals(null, $promise2->result[1]->result);
    }

    public function testLoad()
    {
        /** @var DataLoader $dataLoader */
        [$counter, $dataLoader] = $this->getDataLoader([1 => 'test', 2 => 'other']);

        $dataLoader->load(1)->then(fn($data) => self::assertEquals('test', $data));
        $dataLoader->load(2)->then(fn($data) => self::assertEquals('other', $data));
        $dataLoader->load(2)->then(fn($data) => self::assertEquals(null, $data));

        Deferred::runQueue();
        self::assertEquals(1, $counter->count);
    }

    public function testGetLoadingTraces()
    {
        $dataLoader = new DataLoader(fn() => [1 => 'test', 2 => 'other']);
        $promisses = [
            $dataLoader->load(1),
            $dataLoader->load(1),
            $dataLoader->load(1),
        ];

        Deferred::runQueue();
        self::assertCount(1, $dataLoader->getLoadingTraces());
        self::assertEquals(['test', 'test', 'test'], self::promissesToValues($promisses));
    }
}
