<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoaderIdentifiable;
use GraphQlTools\Helper\DataLoader\SimpleDataLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplObjectStorage;
use stdClass;

class DataLoaderTest extends TestCase
{
    private static function promisesToValues(array $promises): array {
        return array_map(static fn(SyncPromise $promise) => $promise->result, $promises);
    }

    public function testMultipleLoads()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promise1 = $dataLoader->load(2);
        Deferred::runQueue();

        $promise2 = $dataLoader->load(2);
        Deferred::runQueue();

        self::assertEquals('other', $promise1->result);
        self::assertEquals('other', $promise2->result);
    }

    public function testLoadingWithArrayItems()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promise1 = $dataLoader->load(['itemId' => 1, 'args' => []]);
        $promise2 = $dataLoader->load(['itemId' => 2, 'args' => []]);
        Deferred::runQueue();

        self::assertEquals('test', $promise1->result);
        self::assertEquals('other', $promise2->result);
    }

    public function testLoadMany()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => 'other']);

        $promises1 = $dataLoader->loadMany(1, 2);
        $promises2 = $dataLoader->loadMany(1, 3);
        Deferred::runQueue();

        self::assertEquals('test', $promises1[0]->result);
        self::assertEquals('other', $promises1[1]->result);

        self::assertEquals('test', $promises2[0]->result);
        self::assertEquals(null, $promises2[1]->result);
    }

    public function testLoadWithExceptions()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => new RuntimeException('')]);

        $promises1 = $dataLoader->loadMany(1, 2);
        Deferred::runQueue();

        self::assertEquals('test', $promises1[0]->result);
        self::assertInstanceOf(RuntimeException::class, $promises1[1]->result);
        self::assertTrue($promises1[1]->state === SyncPromise::REJECTED);
    }

    public function testLoad()
    {
        $count = 0;
        $dataLoader = new SimpleDataLoader(static function() use (&$count) {
            $count++;
            return [1 => 'test', 2 => 'other'];
        });

        $dataLoader->load(1)->then(fn($data) => self::assertEquals('test', $data));
        $dataLoader->load(2)->then(fn($data) => self::assertEquals('other', $data));
        $dataLoader->load(2)->then(fn($data) => self::assertEquals(null, $data));

        Deferred::runQueue();
        self::assertEquals(1, $count);
    }

    public function testLoadWithError()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => new RuntimeException('Random')]);
        $successfulPromise = $dataLoader->load(1);
        $failedPromise = $dataLoader->load(2);

        Deferred::runQueue();
        self::assertEquals('test', $successfulPromise->result);
        self::assertTrue($failedPromise->state === SyncPromise::REJECTED);
        self::assertInstanceOf(RuntimeException::class, $failedPromise->result);
    }

    public function testGetLoadingTraces()
    {
        $dataLoader = new SimpleDataLoader(fn() => [1 => 'test', 2 => 'other']);
        $promises = [
            $dataLoader->load(1),
            $dataLoader->load(1),
            $dataLoader->load(1),
        ];

        Deferred::runQueue();
        self::assertCount(1, $dataLoader->getLoadingTraces());
        self::assertEquals(['test', 'test', 'test'], self::promisesToValues($promises));
    }

    public function testWithObjectLoad(): void {
        $object1 = new stdClass();
        $object2 = new stdClass();

        $storage = new SplObjectStorage();
        $storage[$object1] = 'object1';
        $storage[$object2] = 'object2';

        $dataLoader = new SimpleDataLoader(fn() => $storage);
        $promise1 = $dataLoader->load($object1);
        $promise2 = $dataLoader->load($object2);

        Deferred::runQueue();
        self::assertEquals('object1', $promise1->result);
        self::assertEquals('object2', $promise2->result);
    }

    public function testWithObjectsIdentifer(): void {
        $object1 = new class () implements DataLoaderIdentifiable {
            public function dataLoaderIdentifier(): int|string
            {
                return 1;
            }
        };

        $object2 = new class () implements DataLoaderIdentifiable {
            public function dataLoaderIdentifier(): int|string
            {
                return 2;
            }
        };

        $storage = [1 => 'I am object 1', 2 => 'I am object 2'];

        $dataLoader = new SimpleDataLoader(fn() => $storage);
        $promise1 = $dataLoader->load($object1);
        $promise2 = $dataLoader->load($object2);

        Deferred::runQueue();
        self::assertEquals('I am object 1', $promise1->result);
        self::assertEquals('I am object 2', $promise2->result);
    }
}
