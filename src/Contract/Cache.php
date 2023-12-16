<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface Cache
{

    public function setResult(?array $result): void;

    public function isInResult(array $path): bool;

    public function getFromResult(array $path): mixed;

    /**
     * @template T
     * @param array $path
     * @param string $key
     * @param T $data
     * @return T
     */
    public function setCache(array $path, string $key, mixed $data): mixed;

    /**
     * @param array $path
     * @param string $key
     * @return mixed
     */
    public function getCache(array $path, string $key): mixed;

}