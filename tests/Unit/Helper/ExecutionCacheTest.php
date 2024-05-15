<?php declare(strict_types=1);

namespace GraphQlTools\Test\Unit\Helper;

use GraphQlTools\Helper\Cache\ExecutionCache;
use PHPUnit\Framework\TestCase;

class ExecutionCacheTest extends TestCase
{
    private ExecutionCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ExecutionCache();
    }

    public function testGetCache()
    {
        self::assertNull($this->cache->getCache('test', 'test'));
        $object = new \stdClass();
        self::assertSame($object, $this->cache->setCache('test', 'test', $object));
        self::assertSame($object, $this->cache->getCache('test', 'test'));
    }

    public function testIsInResult()
    {
        self::assertFalse($this->cache->isInResult(['query', 1, 2, 3, 'value']));
        $this->cache->setResult([
            'query' => [
                'test' => null,
                'other' => [
                    0 => 123
                ]
            ]
        ]);

        self::assertTrue($this->cache->isInResult(['query', 'test']));
        self::assertTrue($this->cache->isInResult(['query', 'other', 0]));
        self::assertTrue($this->cache->isInResult(['query', 'other', '0']));
        self::assertFalse($this->cache->isInResult(['query', 'other', '0', 1]));
    }

    public function testGetFromResult()
    {
        self::assertNull($this->cache->getFromResult(['query', 1, 2, 3, 'value']));
        $this->cache->setResult([
            'query' => [
                'test' => null,
                'other' => [
                    0 => 123
                ]
            ]
        ]);
        self::assertEquals([
            'test' => null,
            'other' => [
                0 => 123
            ]
        ], $this->cache->getFromResult(['query']));
    }
}
