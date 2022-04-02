<?php declare(strict_types=1);

namespace GraphQlTools\Test\Utility;

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
}
