<?php declare(strict_types=1);

namespace GraphQlTools\Immutable;

use GraphQlTools\Utility\Arrays;
use GraphQlTools\Utility\Http;

/**
 * @property-read array|null $requestHeaders
 * @property-read string|null $clientName
 * @property-read string|null $clientVersion
 * @property-read array|null $variables
 */
final class AdditionalExecutionInformation extends Holder
{
    public static function from(?string $clientName, ?string $clientVersion, ?array $requestHeaders, ?array $variables){
        return new self([
            'requestHeaders' => Http::normalizeHeaders($requestHeaders),
            'clientName' => $clientName,
            'clientVersion' => $clientVersion,
            'variables' => $variables
        ]);
    }

    public function hasHeader(string $key): bool {
        return !!$this->getHeader($key);
    }

    public function getHeader(string $key): ?string {
        $normalizedKey = strtolower($key);
        return $this->requestHeaders[$normalizedKey] ?? null;
    }

    public function getHeaders(array $blacklist = []): array {
        $blacklist = array_map('strtolower', $blacklist);
        return Arrays::blacklistKeys($this->requestHeaders, $blacklist, false);
    }

}