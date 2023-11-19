<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper\DataLoader;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Contract\DataLoaderIdentifiable;
use GraphQlTools\Helper\DataLoader\CachedDataLoader;
use PHPUnit\Framework\TestCase;

class CachedDataLoaderTest extends TestCase
{

    public function testLoadByPossibleIds(): void {
        $loader = new CachedDataLoader(fn() => [1 => 'one', 'two' => 'two', 'three' => 'something']);
        $promise1 = $loader->load(1);
        $promise2 = $loader->load('two');
        $promise3 = $loader->load(new class implements DataLoaderIdentifiable {
            public function dataLoaderIdentifier(): int|string
            {
                return 'three';
            }
        });

        SyncPromise::runQueue();
        self::assertEquals('one', $promise1->result);
        self::assertEquals('two', $promise2->result);
        self::assertEquals('something', $promise3->result);
    }

    public function testLoad()
    {
        $loader = new CachedDataLoader(fn() => [1 => 'one', 2 => 'two']);
        $promise1 = $loader->load(1);
        $promise2 = $loader->load(2);
        $promise3 = $loader->load(3);

        self::assertInstanceOf(Deferred::class, $promise1);
        self::assertInstanceOf(Deferred::class, $promise2);
        self::assertInstanceOf(Deferred::class, $promise3);
        SyncPromise::runQueue();

        self::assertEquals('one', $promise1->result);
        self::assertEquals('two', $promise2->result);
        self::assertEquals(null, $promise3->result);

        // Test if cached correctly
        self::assertEquals('one', $loader->load(1));
        self::assertEquals('two', $loader->load(2));
        self::assertEquals(null, $loader->load(3));
    }

    public function testClear()
    {
        $loader = new CachedDataLoader(fn() => [1 => 'one', 2 => 'two']);
        $promise1 = $loader->load(1);
        SyncPromise::runQueue();
        self::assertEquals('one', $promise1->result);
        self::assertEquals('one', $loader->load(1));
        $loader->clear();

        $promise1 = $loader->load(1);
        self::assertInstanceOf(SyncPromise::class, $promise1);
        SyncPromise::runQueue();
        self::assertEquals('one', $promise1->result);
    }

    public function testLoadMany()
    {
        $loader = new CachedDataLoader(fn() => [1 => 'one', 2 => 'two']);
        $promises = $loader->loadMany(1,2,3);
        SyncPromise::runQueue();

        foreach ($promises as $index => $promise) {
            self::assertInstanceOf(SyncPromise::class, $promise);
            match ($index) {
                0 => self::assertEquals('one', $promise->result),
                1 => self::assertEquals('two', $promise->result),
                2 => self::assertEquals(null, $promise->result),
                default => self::fail('unexpected index')
            };
        }

        $promises = $loader->loadMany(1,2,3);
        foreach ($promises as $index => $promise) {
            self::assertNotInstanceOf(SyncPromise::class, $promise);
            match ($index) {
                0 => self::assertEquals('one', $promise),
                1 => self::assertEquals('two', $promise),
                2 => self::assertEquals(null, $promise),
                default => self::fail('unexpected index')
            };
        }
    }
}
