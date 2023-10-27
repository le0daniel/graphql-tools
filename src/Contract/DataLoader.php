<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

/**
 * @template T
 */
interface DataLoader
{

    /**
     * @param mixed $item
     * @return T
     */
    public function load(mixed $item): mixed;

    /**
     * @param mixed ...$items
     * @return T
     */
    public function loadMany(mixed ...$items): mixed;
}