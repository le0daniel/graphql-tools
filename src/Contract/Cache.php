<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface Cache
{

    public function setResult(?array $result): void;

    public function isInResult(array $path): bool;

    public function getFromResult(array $path): mixed;

    /**
     * @template T
     * @param string $path
     * @param string $key
     * @param T $data
     * @return T
     */
    public function setCache(string $path, string $key, mixed $data): mixed;

    /**
     * @param string $path
     * @param string $key
     * @return mixed
     */
    public function getCache(string $path, string $key): mixed;

}