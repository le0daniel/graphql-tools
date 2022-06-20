<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

use GraphQlTools\Test\Dummies\HolderDummy;
use GraphQlTools\Utility\Hmac;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

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

    public function testSerialization()
    {
        self::assertEquals(
            'ae514e0de4fad554f880c8f002d376b5c84e12aede31f9ed21128f2d82583bf1::O:8:"stdClass":0:{}',
            Hmac::serializeAndSign('my-key', new stdClass())
        );
    }

    public function testUnSerialization()
    {
        $unserialized = Hmac::verifyAndUnserialize('my-key', 'ae514e0de4fad554f880c8f002d376b5c84e12aede31f9ed21128f2d82583bf1::O:8:"stdClass":0:{}');
        self::assertInstanceOf(stdClass::class, $unserialized);
    }
}
