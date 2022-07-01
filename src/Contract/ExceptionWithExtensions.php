<?php declare(strict_types=1);

namespace GraphQlTools\Contract;

interface ExceptionWithExtensions
{

    public function getExtensions(): array;

}