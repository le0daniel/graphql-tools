<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface ExceptionWithExtensions
{
    /**
     * Generate an array of extension that is added to the output.
     *
     * @return array
     */
    public function getExtensions(): array;
}