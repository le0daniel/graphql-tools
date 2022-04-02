<?php declare(strict_types=1);

namespace GraphQlTools\Utility;

use RuntimeException;
use Throwable;

class Hmac
{

    public static function signMessage(string $key, string $message): string {
        $signature = hash_hmac('sha256', $message, $key);
        return "$signature::$message";
    }

    public static function verifySignatureAndReturnMessage(string $key, string $signedMessage): string {
        try {
            [$userProvidedSignature, $message] = explode('::', $signedMessage, 2);
        } catch (Throwable) {
            throw new RuntimeException("Invalid message format.");
        }


        if (!$userProvidedSignature) {
            throw new RuntimeException("Invalid message format.");
        }

        $computedSignature = hash_hmac('sha256', $message, $key);
        if (hash_equals($computedSignature, $userProvidedSignature) !== true) {
            throw new RuntimeException("Invalid signature for message provided.");
        }

        return $message;
    }

    public static function serializeAndSign(string $key, mixed $data): string {
        return self::signMessage($key, serialize($data));
    }

    public static function verifyAndUnserialize(string $key, string $serialized): mixed {
        $payload = self::verifySignatureAndReturnMessage($key, $serialized);
        return unserialize($payload);
    }

}