<?php declare(strict_types=1);

namespace GraphQlTools\Test\Data\Models;

use GraphQlTools\Data\Models\Holder;
use GraphQlTools\Test\Dummies\HolderDummy;
use PHPUnit\Framework\TestCase;

class HolderTest extends TestCase
{

    public function testSerialize()
    {
        $dummyHolder = HolderDummy::create([
            'key' => 'value'
        ]);

        $serialized = serialize($dummyHolder);
        $clone = unserialize($serialized);

        self::assertInstanceOf(HolderDummy::class, $clone);
        self::assertEquals($dummyHolder->key, $clone->key);
    }

    public function testSerializationOfNestedHolder(){
        $dummyHolder = HolderDummy::create([
            'key' => 'value',
            'holder' => HolderDummy::create(['single' => true]),
            'test' => ['key' => 'value'],
            'holders' => [
                HolderDummy::create(['multiple' => 'first']),
                HolderDummy::create(['multiple' => 'second']),
            ]
        ]);

        $serialized = serialize($dummyHolder);
        $clone = unserialize($serialized);
        self::assertInstanceOf(HolderDummy::class, $clone);
        self::assertInstanceOf(HolderDummy::class, $clone->holder);
        self::assertInstanceOf(HolderDummy::class, $clone->holders[0]);
        self::assertInstanceOf(HolderDummy::class, $clone->holders[1]);
        self::assertEquals(true, $clone->holder->single);
        self::assertEquals('first', $clone->holders[0]->multiple);
        self::assertEquals('second', $clone->holders[1]->multiple);
    }
}
