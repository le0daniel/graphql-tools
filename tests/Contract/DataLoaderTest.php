<?php declare(strict_types=1);

namespace GraphQlTools\Test\Contract;

use Closure;
use GraphQL\Deferred;
use GraphQlTools\Contract\DataLoader;
use PHPUnit\Framework\TestCase;

class DataLoaderTest extends TestCase
{

    private function getDataLoader(array $data, ?Closure $dataAssertions = null){
        $counter = new class () {
            public int $count = 0;
            public function increase(): void {
                $this->count++;
            }
        };

        return [
            $counter,
            new DataLoader(function(...$args) use ($counter, $data, $dataAssertions) {
                if ($dataAssertions) {
                    $dataAssertions(...$args);
                }
                $counter->increase();
                return $data;
            })
        ];
    }

    public function testMultipleLoads(){
        /** @var DataLoader $dataLoader */
        [$counter, $dataLoader] = $this->getDataLoader([1 => 'test', 2 => 'other']);

        $dataLoader->load(2)->then(fn($data) => self::assertEquals(null, $data));
        Deferred::runQueue();

        $dataLoader->load(2)->then(fn($data) => self::assertEquals(null, $data));
        Deferred::runQueue();

        self::assertEquals(2, $counter->count);

    }

    public function testLoadMany()
    {
        [$counter, $dataLoader] = $this->getDataLoader([1 => 'test', 2 => 'other']);

        $dataLoader->loadMany(1, 2)->then(function (array $items) {
            self::assertEquals('test', $items[0]);
            self::assertEquals('other', $items[1]);
        });

        $dataLoader->loadMany(1, 3)->then(function (array $items) {
            self::assertEquals('test', $items[0]);
            self::assertEquals(null, $items[1]);
        });

        Deferred::runQueue();
        self::assertEquals(1, $counter->count);
    }

    public function testLoadAndMapManually()
    {
        /** @var DataLoader $dataLoader */
        [$counter, $dataLoader] = $this->getDataLoader([1 => 'test', 2 => 'other']);

        $dataLoader->loadAndMapManually(1)->then(function (array $items) {
            self::assertEquals([1 => 'test', 2 => 'other'], $items);
        });

        $dataLoader->loadAndMapManually(1, 2, 3)->then(function (array $items) {
            self::assertEquals([1 => 'test', 2 => 'other'], $items);
        });

        Deferred::runQueue();
        self::assertEquals(1, $counter->count);
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
        /** @var DataLoader $dataLoader */
        [$counter, $dataLoader] = $this->getDataLoader([1 => 'test', 2 => 'other']);
        $dataLoader->load(1);
        $dataLoader->load(1);
        $dataLoader->load(1);
        $dataLoader->load(1);
        $dataLoader->load(1);

        Deferred::runQueue();
        self::assertCount(1, $dataLoader->getLoadingTraces());
    }
}
