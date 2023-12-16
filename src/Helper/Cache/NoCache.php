<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Cache;

use GraphQlTools\Contract\Cache;

final class NoCache implements Cache
{

    public function setResult(?array $result): void
    {}

    public function isInResult(array $path): bool
    {
        return false;
    }

    public function getFromResult(array $path): mixed
    {
        throw new \RuntimeException("Cache is disabled, should not be reached.");
    }

    public function setCache(array $path, string $key, mixed $data): mixed
    {
        return $data;
    }

    public function getCache(array $path, string $key): mixed
    {
        return null;
    }
}