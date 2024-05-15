<?php declare(strict_types=1);

namespace GraphQlTools\Helper\Cache;

use GraphQlTools\Contract\Cache;
use GraphQlTools\Utility\Arrays;
use stdClass;

final class ExecutionCache implements Cache
{
    private array $cache = [];

    private null|array $result = null;

    private readonly stdClass $undefined;

    public static function pathToString(array &$path): string {
        return implode('.', $path);
    }

    public function __construct()
    {
        $this->undefined = new stdClass();
    }

    public function setResult(?array $result): void {
        $this->result = $result;
    }

    public function isInResult(array $path): bool
    {
        return (null !== $this->result) && Arrays::getByPathArray($this->result, $path, $this->undefined) !== $this->undefined;
    }

    public function getFromResult(array $path): mixed
    {
        return Arrays::getByPathArray($this->result ?? [], $path);
    }


    /**
     * @template T
     * Cache during execution. This is used to cache the resolution of 'resolveToType' for
     * unions and interfaces, to allow for more than one run successfully.
     * @param string $key
     * @param T $data
     * @return T
     */
    public function setCache(string $path, string $key, mixed $data): mixed
    {
        $this->cache[$path . ":{$key}"] = $data;
        return $data;
    }

    /**
     * Get an item from the cache
     * @param array $path
     * @param string $key
     * @return mixed
     */
    public function getCache(string $path, string $key): mixed
    {
        return $this->cache[$path . ":{$key}"] ?? null;
    }
}