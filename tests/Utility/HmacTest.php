<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Test\Dummies\HolderDummy;
use GraphQlTools\Utility\Hmac;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HmacTest extends TestCase
{

    public function testVerifySignatureAndReturnMessage()
    {
        self::assertEquals(
            'asdf',
            Hmac::verifySignatureAndReturnMessage('my-key', 'b461a3a8dc9e8f027a183e7e841a3dd1e2b9a7c32eb32e9daeea5e2b0eb6d77b::asdf')
        );
    }

    public function testVerifySignatureWithInvalidFormat()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid message format.');
        Hmac::verifySignatureAndReturnMessage('my-key', 'b461a3a8dc9e8f027a183e7e841a3dd1e2b9a7c32eb32e9daeea5e2b0eb6d77b');
    }

    public function testVerifySignatureWithInvalidMessage()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid message format.');
        Hmac::verifySignatureAndReturnMessage('my-key', '::');
    }

    public function testVerifySignatureWithInvalidSignature()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid signature for message provided.');
        Hmac::verifySignatureAndReturnMessage('my', 'b461a3a8dc9e8f027a183e7e841a3dd1e2b9a7c32eb32e9daeea5e2b0eb6d77b::asdf');
    }

    public function testSignMessage()
    {
        self::assertEquals(
            'b461a3a8dc9e8f027a183e7e841a3dd1e2b9a7c32eb32e9daeea5e2b0eb6d77b::asdf',
            Hmac::signMessage('my-key', 'asdf')
        );
    }

    public function testSerialization(){
        self::assertEquals(
            'e8959909c5e02d5b97d91fd014838e38868d814419e76745a66ec949de54f22d::O:37:"GraphQlTools\Test\Dummies\HolderDummy":1:{s:3:"key";s:5:"value";}',
            Hmac::serializeAndSign('my-key', HolderDummy::create(['key' => 'value']))
        );
    }

    public function testUnSerialization(){
        $unserialized = Hmac::verifyAndUnserialize('my-key', 'e8959909c5e02d5b97d91fd014838e38868d814419e76745a66ec949de54f22d::O:37:"GraphQlTools\Test\Dummies\HolderDummy":1:{s:3:"key";s:5:"value";}');
        self::assertInstanceOf(HolderDummy::class, $unserialized);
    }
}