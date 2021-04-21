<?php

declare(strict_types=1);

namespace GraphQlTools\Test\DataLoader;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQlTools\Test\Dummies\DummyDataLoader;
use PHPUnit\Framework\TestCase;

final class SyncDataLoaderTest extends TestCase {

    public function testLoadSingleBy(): void{
        $loader = new DummyDataLoader();

        $loader->loadSingleBy('one')
            ->then(function(array $data) {
                self::assertEquals('one', $data['id']);
            });

        $loader->loadSingleBy('two')
            ->then(function(array $data) {
                self::assertEquals('two', $data['id']);
            });

        SyncPromise::runQueue();
        self::assertEquals(1, $loader->getLoadCount());
        self::assertEquals(['one', 'two'], $loader->getLoadedIds());
    }

    public function testLoadBy(): void{
        $identifiers = ['one', 'two'];
        $loader = new DummyDataLoader();

        $loader->loadBy(...$identifiers)
            ->then(function(array $data) use ($identifiers) {
                foreach ($data as $item) {
                    self::assertContains($item['id'], $identifiers);
                }
            });

        $loader->loadBy(...$identifiers)
            ->then(function(array $data) use ($identifiers) {
                foreach ($data as $item) {
                    self::assertContains($item['id'], $identifiers);
                }
            });

        SyncPromise::runQueue();
        self::assertEquals(1, $loader->getLoadCount());
        self::assertEquals(['one', 'two'], $loader->getLoadedIds());
    }
}
