<?php declare(strict_types=1);

namespace GraphQlTools\Data\Models;

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
        return !!$this->headerByName($key);
    }

    public function headerByName(string $key): ?string {
        $normalizedKey = strtolower($key);
        return $this->requestHeaders[$normalizedKey] ?? null;
    }

    public function allHeaders(array $blacklist = []): array {
        $blacklist = array_map('strtolower', $blacklist);
        return Arrays::blacklistKeys($this->requestHeaders, $blacklist, false);
    }

}